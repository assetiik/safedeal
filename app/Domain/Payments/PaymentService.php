<?php

namespace App\Domain\Payments;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deals\DealStateMachine;
use App\Domain\Notifications\Notifier;
use App\Domain\Payments\Contracts\PaymentProvider;
use App\Enums\AuditAction;
use App\Enums\DealAction;
use App\Enums\DealStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\ApiException;
use App\Models\Deal;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PaymentService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly DealStateMachine $stateMachine,
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
    ) {}

    public function summary(Deal $deal, User $user): array
    {
        $reserve = $this->successfulReserve($deal);
        $canPay = $this->stateMachine->can($deal, $user, DealAction::ReservePayment) && $reserve === null;

        $flow = match (true) {
            $reserve !== null => 'success',
            $deal->payments()->where('type', PaymentType::Reserve)->where('status', PaymentStatus::Processing)->exists() => 'processing',
            $deal->payments()->where('type', PaymentType::Reserve)->where('status', PaymentStatus::Failed)->exists()
                && $deal->status === DealStatus::AwaitingPayment => 'failure',
            $deal->status === DealStatus::AwaitingPayment => 'awaiting',
            default => $deal->status === DealStatus::AwaitingExecutor || $deal->status === DealStatus::ContractConfirmed
                ? 'awaiting'
                : 'success',
        };

        return [
            'deal_id' => $deal->id,
            'amount_tenge' => $deal->amount_tenge,
            'currency' => $deal->currency,
            'flow_status' => $flow,
            'can_pay' => $canPay,
            'timeline' => $this->timeline($deal, $reserve !== null),
            'masked_card' => null,
            'payment_id' => $reserve?->id,
        ];
    }

    public function reserve(Deal $deal, User $user, string $idempotencyKey): Payment
    {
        $this->stateMachine->assertCan($deal, $user, DealAction::ReservePayment);

        if ($this->successfulReserve($deal)) {
            throw ApiException::conflict('PAYMENT_ALREADY_RESERVED', 'Оплата по этой сделке уже зарезервирована');
        }

        $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ($existing->deal_id !== $deal->id) {
                throw ApiException::conflict('IDEMPOTENCY_KEY_REUSED', 'Ключ идемпотентности уже использован');
            }

            return $existing;
        }

        return DB::transaction(function () use ($deal, $user, $idempotencyKey) {
            $deal->refresh();
            $this->stateMachine->assertCan($deal, $user, DealAction::ReservePayment);

            $payment = Payment::query()->create([
                'deal_id' => $deal->id,
                'type' => PaymentType::Reserve,
                'amount_tenge' => $deal->amount_tenge,
                'currency' => $deal->currency,
                'status' => PaymentStatus::Processing,
                'provider' => $this->provider->code(),
                'direction' => PaymentDirection::Incoming,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->record(AuditAction::PaymentReserveInitiated, $payment, $user, $deal->id, [
                'amount_tenge' => $deal->amount_tenge,
            ]);

            $result = $this->provider->initiateReserve($deal, $payment);

            $payment->update([
                'provider_payment_id' => $result->providerPaymentId,
                'provider_payload' => $result->payload,
                'status' => $result->succeeded() ? PaymentStatus::Succeeded : PaymentStatus::Processing,
            ]);

            if ($result->succeeded()) {
                $this->applyReserveSuccess($deal, $payment, $user);
            }

            return $payment->refresh();
        });
    }

    public function payout(Deal $deal, int $amount, User $actor, PaymentType $type = PaymentType::Payout): Payment
    {
        return $this->outgoing($deal, $amount, $actor, $type, PaymentDirection::Outgoing, AuditAction::PaymentPayoutSucceeded);
    }

    public function refund(Deal $deal, int $amount, User $actor, PaymentType $type = PaymentType::Refund): Payment
    {
        return $this->outgoing($deal, $amount, $actor, $type, PaymentDirection::Incoming, AuditAction::PaymentRefundSucceeded);
    }

    public function handleWebhook(Request $request, string $provider): Payment
    {
        if ($provider !== $this->provider->code()) {
            throw new ApiException('UNKNOWN_PROVIDER', 'Неизвестный платёжный провайдер', 404);
        }

        $payload = $this->provider->verifyWebhook($request);

        $payment = Payment::query()
            ->where('provider_payment_id', $payload->providerPaymentId)
            ->first();

        if ($payment === null) {
            throw ApiException::notFound('Платёж не найден');
        }

        if ($payment->status === PaymentStatus::Succeeded) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $payload) {
            $payment->refresh();
            $deal = $payment->deal()->lockForUpdate()->first();

            if ($payload->status === 'succeeded') {
                $payment->update([
                    'status' => PaymentStatus::Succeeded,
                    'provider_payload' => array_merge($payment->provider_payload ?? [], ['webhook' => $payload->raw]),
                ]);

                if ($payment->type === PaymentType::Reserve) {
                    $this->applyReserveSuccess($deal, $payment, $deal->customer);
                }
            } elseif (in_array($payload->status, ['failed', 'cancelled'], true)) {
                $payment->update(['status' => $payload->status === 'cancelled' ? PaymentStatus::Cancelled : PaymentStatus::Failed]);
            }

            return $payment->refresh();
        });
    }

    public function applyReserveSuccess(Deal $deal, Payment $payment, ?User $actor = null): void
    {
        if ($deal->status !== DealStatus::AwaitingPayment && $deal->status !== DealStatus::MoneyReserved) {
            if ($deal->status === DealStatus::InProgress) {
                return;
            }
        }

        $from = $deal->status;
        $deal->update(['status' => DealStatus::MoneyReserved]);

        $this->audit->record(AuditAction::PaymentReserveSucceeded, $payment, $actor, $deal->id, [
            'from' => $from->value,
            'to' => DealStatus::MoneyReserved->value,
        ]);

        $this->notifier->dealParties(
            $deal->fresh(['customer', 'contractor']),
            NotificationType::PaymentReserved,
            'Деньги зарезервированы',
            "По сделке №{$deal->deal_number} сумма {$deal->amount_tenge} ₸ зарезервирована.",
        );

        if (config('escrow.auto_start_work_on_reserve')) {
            $deal->update(['status' => DealStatus::InProgress]);
            $this->audit->record(AuditAction::DealWorkStarted, $deal, $actor, $deal->id, [
                'note' => 'auto_start_work',
                'to' => DealStatus::InProgress->value,
            ]);
            if ($deal->contractor) {
                $this->notifier->send(
                    $deal->contractor,
                    NotificationType::WorkStarted,
                    'Можно приступать к работе',
                    "По сделке №{$deal->deal_number} средства зарезервированы, статус: в работе.",
                    ['deal_id' => $deal->id],
                );
            }
        }
    }

    private function outgoing(
        Deal $deal,
        int $amount,
        User $actor,
        PaymentType $type,
        PaymentDirection $direction,
        AuditAction $auditAction,
    ): Payment {
        if ($amount < 0) {
            throw new ApiException('INVALID_AMOUNT', 'Сумма не может быть отрицательной', 400);
        }

        if ($amount === 0) {
            throw new ApiException('INVALID_AMOUNT', 'Сумма должна быть больше нуля', 400);
        }

        $payment = Payment::query()->create([
            'deal_id' => $deal->id,
            'type' => $type,
            'amount_tenge' => $amount,
            'currency' => $deal->currency,
            'status' => PaymentStatus::Processing,
            'provider' => $this->provider->code(),
            'direction' => $direction,
            'idempotency_key' => $type->value.':'.$deal->id.':'.Str::uuid(),
        ]);

        $result = $type === PaymentType::Refund || $type === PaymentType::PartialRefund
            ? $this->provider->initiateRefund($deal, $payment)
            : $this->provider->initiatePayout($deal, $payment);

        $payment->update([
            'status' => $result->succeeded() ? PaymentStatus::Succeeded : PaymentStatus::Processing,
            'provider_payment_id' => $result->providerPaymentId,
            'provider_payload' => $result->payload,
        ]);

        if ($result->succeeded()) {
            $this->audit->record($auditAction, $payment, $actor, $deal->id, [
                'amount_tenge' => $amount,
                'type' => $type->value,
            ]);
        }

        return $payment->refresh();
    }

    public function successfulReserve(Deal $deal): ?Payment
    {
        return $deal->payments()
            ->where('type', PaymentType::Reserve)
            ->where('status', PaymentStatus::Succeeded)
            ->first();
    }

    /**
     * @return list<array{type: string, completed: bool, current: bool}>
     */
    private function timeline(Deal $deal, bool $reserved): array
    {
        $steps = ['deal_created', 'contract_confirmed', 'payment_started', 'money_reserved'];
        $completedUntil = match (true) {
            $reserved || in_array($deal->status, [DealStatus::MoneyReserved, DealStatus::InProgress, DealStatus::WorkCompleted, DealStatus::AwaitingCustomer, DealStatus::Completed, DealStatus::Dispute, DealStatus::Refunded], true) => 3,
            $deal->status === DealStatus::AwaitingPayment => 2,
            $deal->status === DealStatus::ContractConfirmed => 1,
            default => 0,
        };

        $current = min($completedUntil + ($completedUntil < 3 ? 1 : 0), 3);

        return collect($steps)->map(fn (string $type, int $i) => [
            'type' => $type,
            'completed' => $i <= $completedUntil,
            'current' => $i === $current && $completedUntil < 3,
        ])->all();
    }
}

<?php

namespace App\Domain\Deals;

use App\Domain\Audit\AuditLogger;
use App\Domain\Contracts\ContractGenerator;
use App\Domain\Documents\DocumentService;
use App\Domain\Notifications\Notifier;
use App\Domain\Payments\PaymentService;
use App\Enums\AuditAction;
use App\Enums\DealAction;
use App\Enums\DealStatus;
use App\Enums\DocumentType;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DealService
{
    public function __construct(
        private readonly DealStateMachine $stateMachine,
        private readonly ContractGenerator $contracts,
        private readonly PaymentService $payments,
        private readonly DocumentService $documents,
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
    ) {}

    public function create(User $customer, array $payload): Deal
    {
        if (! $customer->isCustomer()) {
            throw ApiException::forbidden('Создать сделку может только заказчик');
        }

        $email = Str::lower($payload['executor_email']);

        if ($email === Str::lower($customer->email)) {
            throw new ApiException('INVALID_CONTRACTOR', 'Нельзя пригласить самого себя', 400);
        }

        $contractor = User::query()->where('email', $email)->first();

        if ($contractor && $contractor->role !== UserRole::Contractor) {
            throw new ApiException('INVITE_NOT_CONTRACTOR', 'Указанный email принадлежит не исполнителю', 422);
        }

        $amount = (int) $payload['amount_tenge'];
        $rate = (int) config('escrow.commission_rate_bps', 0);
        $commission = intdiv($amount * $rate, 10_000);

        $deal = DB::transaction(function () use ($customer, $payload, $email, $contractor, $amount, $rate, $commission) {
            $deal = Deal::query()->create([
                'deal_number' => $this->nextNumber(),
                'status' => DealStatus::AwaitingExecutor,
                'title' => $payload['title'],
                'description' => $payload['description'],
                'amount_tenge' => $amount,
                'currency' => config('escrow.currency', 'KZT'),
                'commission_rate_bps' => $rate,
                'commission_amount_tenge' => $commission,
                'deadline' => $payload['deadline'],
                'terms' => $payload['terms'],
                'additional_terms' => $payload['additional_terms'] ?? null,
                'required_documents' => $payload['required_documents'] ?? [],
                'customer_user_id' => $customer->id,
                'contractor_user_id' => $contractor?->id,
                'contractor_invite_email' => $email,
            ]);

            $body = $this->contracts->render($deal->load(['customer.profile', 'contractor.profile']));

            Contract::query()->create([
                'deal_id' => $deal->id,
                'template_version' => ContractGenerator::VERSION,
                'body_snapshot' => $body,
                'signature_type' => 'simple',
            ]);

            $this->audit->record(AuditAction::DealCreated, $deal, $customer, $deal->id, [
                'title' => $deal->title,
                'amount_tenge' => $deal->amount_tenge,
                'contractor_invite_email' => $email,
            ]);

            return $deal;
        });

        $deal->load(['customer.profile', 'contractor.profile', 'contract']);

        $this->notifier->send(
            $customer,
            NotificationType::DealCreated,
            'Сделка создана',
            "Сделка №{$deal->deal_number} «{$deal->title}» создана. Ожидаем исполнителя.",
            ['deal_id' => $deal->id],
        );

        if ($contractor) {
            $this->notifier->send(
                $contractor,
                NotificationType::DealInvitation,
                'Приглашение в сделку',
                "Вас пригласили в сделку №{$deal->deal_number}: «{$deal->title}».",
                ['deal_id' => $deal->id],
            );
        }

        return $deal;
    }

    public function applyAction(Deal $deal, User $user, DealAction $action): Deal
    {
        return match ($action) {
            DealAction::AcceptInvitation => $this->accept($deal, $user),
            DealAction::DeclineInvitation => $this->decline($deal, $user),
            DealAction::ConfirmContract => $this->confirmContract($deal, $user),
            DealAction::MarkWorkCompleted => $this->markWorkCompleted($deal, $user),
            DealAction::ConfirmCompletion => $this->confirmCompletion($deal, $user),
            DealAction::OpenDispute => throw new ApiException('USE_DISPUTE_ENDPOINT', 'Откройте спор через POST /deals/{id}/disputes', 422),
        };
    }

    public function accept(Deal $deal, User $user): Deal
    {
        $this->stateMachine->assertCan($deal, $user, DealAction::AcceptInvitation);

        return DB::transaction(function () use ($deal, $user) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->stateMachine->assertCan($deal, $user, DealAction::AcceptInvitation);

            $deal->update([
                'status' => DealStatus::ContractConfirmed,
                'contractor_user_id' => $user->id,
            ]);

            $this->refreshContractBody($deal);

            $this->audit->record(AuditAction::DealAccepted, $deal, $user, $deal->id, [
                'from' => DealStatus::AwaitingExecutor->value,
                'to' => DealStatus::ContractConfirmed->value,
            ]);

            $deal = $deal->fresh(['customer', 'contractor']);

            $this->notifier->send(
                $deal->customer,
                NotificationType::DealInvitation,
                'Исполнитель принял сделку',
                "Исполнитель принял сделку №{$deal->deal_number}. Подтвердите договор.",
                ['deal_id' => $deal->id],
            );

            return $deal;
        });
    }

    public function decline(Deal $deal, User $user): Deal
    {
        $this->stateMachine->assertCan($deal, $user, DealAction::DeclineInvitation);

        return DB::transaction(function () use ($deal, $user) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->stateMachine->assertCan($deal, $user, DealAction::DeclineInvitation);

            $deal->update(['status' => DealStatus::Draft]);

            $this->audit->record(AuditAction::DealDeclined, $deal, $user, $deal->id, [
                'from' => DealStatus::AwaitingExecutor->value,
                'to' => DealStatus::Draft->value,
            ]);

            $deal = $deal->fresh(['customer', 'contractor']);
            $this->notifier->send(
                $deal->customer,
                NotificationType::DealInvitation,
                'Исполнитель отклонил сделку',
                "Исполнитель отклонил сделку №{$deal->deal_number}.",
                ['deal_id' => $deal->id],
            );

            return $deal;
        });
    }

    public function confirmContract(Deal $deal, User $user, bool $acknowledged = true): Deal
    {
        if (! $acknowledged) {
            throw new ApiException('CONTRACT_NOT_ACKNOWLEDGED', 'Необходимо принять условия договора', 400);
        }

        $this->stateMachine->assertCan($deal, $user, DealAction::ConfirmContract);

        return DB::transaction(function () use ($deal, $user) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->stateMachine->assertCan($deal, $user, DealAction::ConfirmContract);

            $contract = $deal->contract()->lockForUpdate()->firstOrFail();

            if ($user->isCustomer()) {
                $deal->customer_confirmed_contract = true;
                $contract->customer_confirmed_at = now();
            } else {
                $deal->contractor_confirmed_contract = true;
                $contract->contractor_confirmed_at = now();
            }

            $both = $deal->customer_confirmed_contract && $deal->contractor_confirmed_contract;

            if ($both) {
                $deal->status = DealStatus::AwaitingPayment;
                $contract->body_snapshot = $this->contracts->render($deal->load(['customer.profile', 'contractor.profile']));
            }

            $deal->save();
            $contract->save();

            $this->audit->record(AuditAction::DealContractConfirmed, $deal, $user, $deal->id, [
                'party' => $user->role->value,
                'both' => $both,
            ]);

            $deal = $deal->fresh(['customer', 'contractor', 'contract']);

            if ($both) {
                $this->documents->storeGenerated(
                    $deal,
                    (string) $contract->body_snapshot,
                    "contract-{$deal->deal_number}.txt",
                    "Договор №{$deal->deal_number}",
                    DocumentType::Contract,
                    $user,
                );

                $this->notifier->dealParties(
                    $deal,
                    NotificationType::ContractConfirmedBoth,
                    'Договор подтверждён',
                    "Обе стороны подтвердили договор по сделке №{$deal->deal_number}. Можно переходить к оплате.",
                );
            } else {
                $other = $user->isCustomer() ? $deal->contractor : $deal->customer;
                if ($other) {
                    $this->notifier->send(
                        $other,
                        NotificationType::ContractConfirmedPartial,
                        'Договор подтверждён одной стороной',
                        "По сделке №{$deal->deal_number} договор подтверждён. Ожидается подтверждение второй стороны.",
                        ['deal_id' => $deal->id],
                    );
                }
            }

            return $deal;
        });
    }

    public function markWorkCompleted(Deal $deal, User $user): Deal
    {
        $this->stateMachine->assertCan($deal, $user, DealAction::MarkWorkCompleted);

        return DB::transaction(function () use ($deal, $user) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->stateMachine->assertCan($deal, $user, DealAction::MarkWorkCompleted);

            $from = $deal->status;
            $deal->update(['status' => DealStatus::WorkCompleted]);
            $this->audit->record(AuditAction::DealWorkCompleted, $deal, $user, $deal->id, [
                'from' => $from->value,
                'to' => DealStatus::WorkCompleted->value,
            ]);

            $deal->update(['status' => DealStatus::AwaitingCustomer]);
            $this->audit->record(AuditAction::DealWorkCompleted, $deal, $user, $deal->id, [
                'from' => DealStatus::WorkCompleted->value,
                'to' => DealStatus::AwaitingCustomer->value,
            ]);

            $deal = $deal->fresh(['customer', 'contractor']);
            $this->notifier->send(
                $deal->customer,
                NotificationType::ConfirmationRequired,
                'Подтвердите выполнение работы',
                "Исполнитель отметил работу по сделке №{$deal->deal_number} выполненной. Подтвердите результат или откройте спор.",
                ['deal_id' => $deal->id],
            );
            $this->notifier->send(
                $deal->contractor,
                NotificationType::WorkCompleted,
                'Работа отмечена выполненной',
                "По сделке №{$deal->deal_number} ожидается подтверждение заказчика.",
                ['deal_id' => $deal->id],
            );

            return $deal;
        });
    }

    public function confirmCompletion(Deal $deal, User $user): Deal
    {
        $this->stateMachine->assertCan($deal, $user, DealAction::ConfirmCompletion);

        if ($this->payments->successfulReserve($deal) === null) {
            throw new ApiException('RESERVE_MISSING', 'Нет успешного резерва средств', 409);
        }

        return DB::transaction(function () use ($deal, $user) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->stateMachine->assertCan($deal, $user, DealAction::ConfirmCompletion);

            $amount = $deal->reservedAmount();
            $this->payments->payout($deal, $amount, $user, PaymentType::Payout);

            $deal->update(['status' => DealStatus::Completed, 'funds_frozen' => false]);

            $this->audit->record(AuditAction::DealCompletionConfirmed, $deal, $user, $deal->id, [
                'to' => DealStatus::Completed->value,
                'payout_tenge' => $amount,
            ]);

            $deal = $deal->fresh(['customer', 'contractor']);
            $this->notifier->dealParties(
                $deal,
                NotificationType::PayoutCompleted,
                'Выплата исполнителю выполнена',
                "Сделка №{$deal->deal_number} завершена. Исполнителю выплачено {$amount} ₸.",
            );

            return $deal;
        });
    }

    public function forceStatus(Deal $deal, User $admin, DealStatus $status, string $reason): Deal
    {
        if (! $admin->isAdmin()) {
            throw ApiException::forbidden();
        }

        return DB::transaction(function () use ($deal, $admin, $status, $reason) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $from = $deal->status;
            $deal->update(['status' => $status]);

            $this->audit->record(AuditAction::DealStatusForced, $deal, $admin, $deal->id, [
                'from' => $from->value,
                'to' => $status->value,
                'reason' => $reason,
            ]);

            return $deal->fresh();
        });
    }

    public function contractPayload(Deal $deal, User $user): array
    {
        $contract = $deal->contract;
        $body = $contract?->body_snapshot ?: $this->contracts->render($deal);

        if ($contract && $contract->body_snapshot === null) {
            $contract->update(['body_snapshot' => $body]);
        }

        return [
            'deal_id' => $deal->id,
            'template_version' => $contract?->template_version ?? ContractGenerator::VERSION,
            'body' => $body,
            'signature_type' => $contract?->signature_type ?? 'simple',
            'customer_confirmed' => (bool) $deal->customer_confirmed_contract,
            'contractor_confirmed' => (bool) $deal->contractor_confirmed_contract,
            'customer_confirmed_at' => $contract?->customer_confirmed_at,
            'contractor_confirmed_at' => $contract?->contractor_confirmed_at,
            'can_confirm' => $this->stateMachine->can($deal, $user, DealAction::ConfirmContract),
        ];
    }

    private function refreshContractBody(Deal $deal): void
    {
        $deal->load(['customer.profile', 'contractor.profile', 'contract']);
        if ($deal->contract) {
            $deal->contract->update(['body_snapshot' => $this->contracts->render($deal)]);
        }
    }

    private function nextNumber(): int
    {
        $max = Deal::query()->lockForUpdate()->max('deal_number');

        return ((int) $max) + 1;
    }
}

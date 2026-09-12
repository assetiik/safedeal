<?php

namespace App\Domain\Deals;

use App\Domain\Audit\AuditLogger;
use App\Domain\Notifications\Notifier;
use App\Domain\Payments\PaymentService;
use App\Enums\AuditAction;
use App\Enums\DealAction;
use App\Enums\DealStatus;
use App\Enums\DisputeResolutionType;
use App\Enums\DisputeStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Exceptions\ApiException;
use App\Models\Deal;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DisputeService
{
    public function __construct(
        private readonly DealStateMachine $stateMachine,
        private readonly PaymentService $payments,
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
    ) {}

    public function open(Deal $deal, User $user, string $reason): Dispute
    {
        $this->stateMachine->assertCan($deal, $user, DealAction::OpenDispute);

        if ($deal->dispute && $deal->dispute->status->isActive()) {
            throw ApiException::conflict('DISPUTE_ALREADY_OPEN', 'По сделке уже открыт активный спор');
        }

        if ($deal->dispute) {
            throw ApiException::conflict('DISPUTE_EXISTS', 'По сделке уже есть спор');
        }

        return DB::transaction(function () use ($deal, $user, $reason) {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->stateMachine->assertCan($deal, $user, DealAction::OpenDispute);

            $deal->update([
                'status' => DealStatus::Dispute,
                'funds_frozen' => true,
            ]);

            $dispute = Dispute::query()->create([
                'deal_id' => $deal->id,
                'status' => DisputeStatus::Open,
                'reason' => $reason,
                'opened_by_user_id' => $user->id,
            ]);

            $dispute->events()->create([
                'type' => 'opened',
                'actor_user_id' => $user->id,
                'payload' => ['reason' => $reason],
            ]);

            $this->audit->record(AuditAction::DisputeOpened, $dispute, $user, $deal->id, [
                'reason' => $reason,
            ]);

            $deal = $deal->fresh(['customer', 'contractor']);
            $this->notifier->dealParties(
                $deal,
                NotificationType::DisputeOpened,
                'Открыт спор',
                "По сделке №{$deal->deal_number} открыт спор. Средства заморожены до решения администратора.",
                ['dispute_id' => $dispute->id],
            );

            User::query()->where('role', 'admin')->where('status', 'active')->get()
                ->each(function (User $admin) use ($deal, $dispute) {
                    $this->notifier->send(
                        $admin,
                        NotificationType::DisputeOpened,
                        'Новый спор',
                        "Сделка №{$deal->deal_number}: открыт спор.",
                        ['deal_id' => $deal->id, 'dispute_id' => $dispute->id],
                        email: true,
                    );
                });

            return $dispute->fresh(['deal', 'openedBy', 'events']);
        });
    }

    public function take(Dispute $dispute, User $admin): Dispute
    {
        $this->assertAdmin($admin);

        if ($dispute->status !== DisputeStatus::Open) {
            throw ApiException::conflict('DISPUTE_NOT_OPEN', 'Спор нельзя взять в работу в текущем статусе');
        }

        return DB::transaction(function () use ($dispute, $admin) {
            $dispute = Dispute::query()->lockForUpdate()->findOrFail($dispute->id);
            $dispute->update(['status' => DisputeStatus::InReview]);
            $dispute->events()->create([
                'type' => 'taken',
                'actor_user_id' => $admin->id,
            ]);
            $this->audit->record(AuditAction::DisputeTaken, $dispute, $admin, $dispute->deal_id);

            return $dispute->fresh(['deal', 'events']);
        });
    }

    public function resolve(Dispute $dispute, User $admin, array $payload): Dispute
    {
        $this->assertAdmin($admin);

        if (! $dispute->status->isActive()) {
            throw ApiException::conflict('DISPUTE_ALREADY_RESOLVED', 'Спор уже решён');
        }

        $type = DisputeResolutionType::from($payload['resolution_type']);
        $deal = $dispute->deal;
        $reserved = $deal->reservedAmount();

        [$customerAmount, $contractorAmount] = $this->split($type, $reserved, $payload);

        return DB::transaction(function () use ($dispute, $admin, $payload, $type, $deal, $customerAmount, $contractorAmount) {
            $dispute = Dispute::query()->lockForUpdate()->findOrFail($dispute->id);
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);

            if ($contractorAmount > 0) {
                $payoutType = $type === DisputeResolutionType::Partial ? PaymentType::PartialPayout : PaymentType::Payout;
                $this->payments->payout($deal, $contractorAmount, $admin, $payoutType);
            }

            if ($customerAmount > 0) {
                $refundType = $type === DisputeResolutionType::Partial ? PaymentType::PartialRefund : PaymentType::Refund;
                $this->payments->refund($deal, $customerAmount, $admin, $refundType);
            }

            $dealStatus = match ($type) {
                DisputeResolutionType::RefundCustomer => DealStatus::Refunded,
                DisputeResolutionType::PayoutContractor => DealStatus::PayoutCompleted,
                DisputeResolutionType::Partial => $contractorAmount > 0
                    ? DealStatus::PayoutCompleted
                    : DealStatus::Refunded,
            };

            $deal->update([
                'status' => $dealStatus,
                'funds_frozen' => false,
            ]);

            $dispute->update([
                'status' => DisputeStatus::Resolved,
                'resolution_type' => $type,
                'resolution_note' => $payload['resolution_note'] ?? null,
                'customer_amount_tenge' => $customerAmount,
                'contractor_amount_tenge' => $contractorAmount,
                'resolved_by_admin_id' => $admin->id,
                'resolved_at' => now(),
            ]);

            $dispute->events()->create([
                'type' => 'resolved',
                'actor_user_id' => $admin->id,
                'payload' => [
                    'resolution_type' => $type->value,
                    'customer_amount_tenge' => $customerAmount,
                    'contractor_amount_tenge' => $contractorAmount,
                ],
            ]);

            $this->audit->record(AuditAction::DisputeResolved, $dispute, $admin, $deal->id, [
                'resolution_type' => $type->value,
                'customer_amount_tenge' => $customerAmount,
                'contractor_amount_tenge' => $contractorAmount,
                'deal_status' => $dealStatus->value,
            ]);

            $deal = $deal->fresh(['customer', 'contractor']);
            $note = $payload['resolution_note'] ?? 'Решение принято администратором.';

            $this->notifier->dealParties(
                $deal,
                NotificationType::DisputeResolved,
                'Спор решён',
                "По сделке №{$deal->deal_number} спор закрыт. {$note}",
                ['dispute_id' => $dispute->id],
            );

            if ($customerAmount > 0) {
                $this->notifier->send(
                    $deal->customer,
                    NotificationType::RefundCompleted,
                    'Возврат средств',
                    "По сделке №{$deal->deal_number} вам возвращено {$customerAmount} ₸.",
                    ['deal_id' => $deal->id],
                );
            }

            if ($contractorAmount > 0 && $deal->contractor) {
                $this->notifier->send(
                    $deal->contractor,
                    NotificationType::PayoutCompleted,
                    'Выплата по решению спора',
                    "По сделке №{$deal->deal_number} вам выплачено {$contractorAmount} ₸.",
                    ['deal_id' => $deal->id],
                );
            }

            return $dispute->fresh(['deal', 'events', 'resolvedBy']);
        });
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function split(DisputeResolutionType $type, int $reserved, array $payload): array
    {
        return match ($type) {
            DisputeResolutionType::PayoutContractor => [0, $reserved],
            DisputeResolutionType::RefundCustomer => [$reserved, 0],
            DisputeResolutionType::Partial => $this->assertPartial($reserved, $payload),
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function assertPartial(int $reserved, array $payload): array
    {
        $customer = (int) ($payload['customer_amount_tenge'] ?? -1);
        $contractor = (int) ($payload['contractor_amount_tenge'] ?? -1);

        if ($customer < 0 || $contractor < 0) {
            throw new ApiException('INVALID_PARTIAL', 'Суммы partial должны быть ≥ 0', 400);
        }

        if ($customer + $contractor !== $reserved) {
            throw new ApiException('PARTIAL_SUM_MISMATCH', 'Сумма частей должна равняться зарезервированной сумме', 400, [
                'reserved_tenge' => $reserved,
                'customer_amount_tenge' => $customer,
                'contractor_amount_tenge' => $contractor,
            ]);
        }

        if ($customer + $contractor === 0) {
            throw new ApiException('INVALID_PARTIAL', 'Хотя бы одна часть должна быть больше нуля', 400);
        }

        return [$customer, $contractor];
    }

    private function assertAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw ApiException::forbidden();
        }
    }
}

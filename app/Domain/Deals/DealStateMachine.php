<?php

namespace App\Domain\Deals;

use App\Enums\DealAction;
use App\Enums\DealStatus;
use App\Enums\DealVisibility;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Str;

final class DealStateMachine
{
    /**
     * @return list<DealStatus>
     */
    public function allowedFrom(DealAction $action): array
    {
        return match ($action) {
            DealAction::AcceptInvitation, DealAction::DeclineInvitation, DealAction::Claim => [DealStatus::AwaitingExecutor],
            DealAction::ConfirmContract => [DealStatus::ContractConfirmed],
            DealAction::ReservePayment => [DealStatus::AwaitingPayment],
            DealAction::MarkWorkCompleted => [DealStatus::MoneyReserved, DealStatus::InProgress],
            DealAction::ConfirmCompletion => [DealStatus::WorkCompleted, DealStatus::AwaitingCustomer],
            DealAction::OpenDispute => DealStatus::disputable(),
        };
    }

    public function can(Deal $deal, User $user, DealAction $action): bool
    {
        try {
            $this->assertCan($deal, $user, $action);

            return true;
        } catch (ApiException) {
            return false;
        }
    }

    public function assertCan(Deal $deal, User $user, DealAction $action): void
    {
        if ($user->status->value !== 'active') {
            throw ApiException::blocked();
        }

        if ($action === DealAction::Claim) {
            $this->assertCanClaim($deal, $user);

            return;
        }

        if (! $deal->isParticipant($user)) {
            throw ApiException::notFound('Сделка не найдена');
        }

        if (! in_array($deal->status, $this->allowedFrom($action), true)) {
            throw ApiException::invalidTransition($deal->status, $action);
        }

        match ($action) {
            DealAction::AcceptInvitation, DealAction::DeclineInvitation => $this->assertInvitee($deal, $user),
            DealAction::MarkWorkCompleted => $this->assertRole($user, UserRole::Contractor, $action),
            DealAction::ReservePayment, DealAction::ConfirmCompletion => $this->assertRole($user, UserRole::Customer, $action),
            DealAction::ConfirmContract => $this->assertCanConfirmContract($deal, $user),
            DealAction::OpenDispute => $this->assertParty($deal, $user),
            DealAction::Claim => $this->assertCanClaim($deal, $user),
        };
    }

    /**
     * @return array<string, bool>
     */
    public function permissions(Deal $deal, User $user): array
    {
        $permissions = [];

        foreach (DealAction::cases() as $action) {
            $permissions[$action->value] = $this->can($deal, $user, $action);
        }

        $permissions['pay'] = $this->can($deal, $user, DealAction::ReservePayment)
            && ! $deal->payments()->where('type', 'reserve')->where('status', 'succeeded')->exists();

        return $permissions;
    }

    private function assertRole(User $user, UserRole $role, DealAction $action): void
    {
        if ($user->role !== $role) {
            throw ApiException::forbidden('Это действие недоступно для вашей роли', [
                'action' => $action->value,
                'role' => $user->role->value,
            ]);
        }
    }

    private function assertParty(Deal $deal, User $user): void
    {
        if ($user->id !== $deal->customer_user_id && $user->id !== $deal->contractor_user_id) {
            throw ApiException::forbidden('Только участники сделки могут выполнить это действие');
        }
    }

    private function assertInvitee(Deal $deal, User $user): void
    {
        $this->assertRole($user, UserRole::Contractor, DealAction::AcceptInvitation);

        if ($deal->visibility === DealVisibility::Public && ! filled($deal->contractor_invite_email)) {
            throw ApiException::forbidden('Для открытого заказа используйте действие claim');
        }

        if ($deal->contractor_user_id !== null && $deal->contractor_user_id !== $user->id) {
            throw ApiException::notFound('Сделка не найдена');
        }

        if (Str::lower((string) $deal->contractor_invite_email) !== Str::lower($user->email)) {
            throw ApiException::forbidden('Приглашение отправлено на другой email');
        }
    }

    private function assertCanClaim(Deal $deal, User $user): void
    {
        $this->assertRole($user, UserRole::Contractor, DealAction::Claim);

        if ($deal->customer_user_id === $user->id) {
            throw ApiException::forbidden('Нельзя откликнуться на собственный заказ');
        }

        if ($deal->contractor_user_id !== null) {
            throw ApiException::conflict('DEAL_ALREADY_CLAIMED', 'Заказ уже занят другим исполнителем');
        }

        if (! in_array($deal->status, $this->allowedFrom(DealAction::Claim), true)) {
            throw ApiException::invalidTransition($deal->status, DealAction::Claim);
        }

        if ($deal->visibility !== DealVisibility::Public) {
            throw ApiException::forbidden('Откликнуться можно только на открытый заказ');
        }
    }

    private function assertCanConfirmContract(Deal $deal, User $user): void
    {
        if ($user->isCustomer() && $user->id === $deal->customer_user_id) {
            if ($deal->customer_confirmed_contract) {
                throw ApiException::conflict('CONTRACT_ALREADY_CONFIRMED', 'Вы уже подтвердили договор');
            }

            return;
        }

        if ($user->isContractor() && $deal->isParticipant($user)) {
            if ($deal->contractor_confirmed_contract) {
                throw ApiException::conflict('CONTRACT_ALREADY_CONFIRMED', 'Вы уже подтвердили договор');
            }

            return;
        }

        throw ApiException::forbidden('Подтвердить договор могут только стороны сделки');
    }
}

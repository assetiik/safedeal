<?php

namespace App\Enums;

enum DealStatus: string
{
    case Draft = 'draft';
    case AwaitingExecutor = 'awaiting_executor';
    case ContractConfirmed = 'contract_confirmed';
    case AwaitingPayment = 'awaiting_payment';
    case MoneyReserved = 'money_reserved';
    case InProgress = 'in_progress';
    case WorkCompleted = 'work_completed';
    case AwaitingCustomer = 'awaiting_customer';
    case Completed = 'completed';
    case Dispute = 'dispute';
    case Refunded = 'refunded';
    case PayoutCompleted = 'payout_completed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Refunded, self::PayoutCompleted], true);
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Refunded, self::PayoutCompleted, self::Draft], true);
    }

    /**
     * @return list<self>
     */
    public static function disputable(): array
    {
        return [
            self::MoneyReserved,
            self::InProgress,
            self::WorkCompleted,
            self::AwaitingCustomer,
        ];
    }

    public function labelRu(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::AwaitingExecutor => 'Ожидает исполнителя',
            self::ContractConfirmed => 'Договор подтверждён',
            self::AwaitingPayment => 'Ожидает оплаты',
            self::MoneyReserved => 'Деньги зарезервированы',
            self::InProgress => 'В работе',
            self::WorkCompleted => 'Работа выполнена',
            self::AwaitingCustomer => 'Ожидает подтверждения',
            self::Completed => 'Завершена',
            self::PayoutCompleted => 'Выплата исполнителю',
            self::Dispute => 'Спор',
            self::Refunded => 'Возврат средств',
        };
    }
}

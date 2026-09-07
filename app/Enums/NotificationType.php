<?php

namespace App\Enums;

enum NotificationType: string
{
    case DealInvitation = 'deal_invitation';
    case DealCreated = 'deal_created';
    case ContractConfirmedPartial = 'contract_confirmed_partial';
    case ContractConfirmedBoth = 'contract_confirmed_both';
    case PaymentReserved = 'payment_reserved';
    case WorkStarted = 'work_started';
    case WorkCompleted = 'work_completed';
    case ConfirmationRequired = 'confirmation_required';
    case PayoutCompleted = 'payout_completed';
    case DisputeOpened = 'dispute_opened';
    case DisputeResolved = 'dispute_resolved';
    case RefundCompleted = 'refund_completed';
    case AccountSecurity = 'account_security';

    public function category(): NotificationCategory
    {
        return match ($this) {
            self::PaymentReserved, self::PayoutCompleted, self::RefundCompleted => NotificationCategory::Payments,
            self::AccountSecurity => NotificationCategory::System,
            default => NotificationCategory::Deals,
        };
    }
}

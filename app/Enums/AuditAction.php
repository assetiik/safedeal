<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserRegistered = 'user.registered';
    case UserLoggedIn = 'user.logged_in';
    case UserBlocked = 'user.blocked';
    case UserUnblocked = 'user.unblocked';
    case ProfileUpdated = 'profile.updated';
    case DealCreated = 'deal.created';
    case DealAccepted = 'deal.accepted';
    case DealDeclined = 'deal.declined';
    case DealContractConfirmed = 'deal.contract_confirmed';
    case DealStatusForced = 'deal.status_forced';
    case DealWorkStarted = 'deal.work_started';
    case DealWorkCompleted = 'deal.work_completed';
    case DealCompletionConfirmed = 'deal.completion_confirmed';
    case PaymentReserveInitiated = 'payment.reserve_initiated';
    case PaymentReserveSucceeded = 'payment.reserve_succeeded';
    case PaymentPayoutSucceeded = 'payment.payout_succeeded';
    case PaymentRefundSucceeded = 'payment.refund_succeeded';
    case DisputeOpened = 'dispute.opened';
    case DisputeTaken = 'dispute.taken';
    case DisputeResolved = 'dispute.resolved';
    case DocumentUploaded = 'document.uploaded';
    case DocumentGenerated = 'document.generated';
}

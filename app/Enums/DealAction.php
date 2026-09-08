<?php

namespace App\Enums;

enum DealAction: string
{
    case AcceptInvitation = 'accept_invitation';
    case DeclineInvitation = 'decline_invitation';
    case Claim = 'claim';
    case ConfirmContract = 'confirm_contract';
    case ReservePayment = 'reserve_payment';
    case MarkWorkCompleted = 'mark_work_completed';
    case ConfirmCompletion = 'confirm_completion';
    case OpenDispute = 'open_dispute';

    /**
     * @return list<self>
     */
    public static function viaActionsEndpoint(): array
    {
        return [
            self::AcceptInvitation,
            self::DeclineInvitation,
            self::Claim,
            self::ConfirmContract,
            self::MarkWorkCompleted,
            self::ConfirmCompletion,
        ];
    }
}

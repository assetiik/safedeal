<?php

namespace App\Enums;

enum DisputeResolutionType: string
{
    case PayoutContractor = 'payout_contractor';
    case RefundCustomer = 'refund_customer';
    case Partial = 'partial';
}

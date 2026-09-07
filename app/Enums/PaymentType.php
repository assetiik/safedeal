<?php

namespace App\Enums;

enum PaymentType: string
{
    case Reserve = 'reserve';
    case Payout = 'payout';
    case Refund = 'refund';
    case PartialPayout = 'partial_payout';
    case PartialRefund = 'partial_refund';
}

<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case Deals = 'deals';
    case Payments = 'payments';
    case System = 'system';
}

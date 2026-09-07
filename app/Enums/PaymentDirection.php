<?php

namespace App\Enums;

enum PaymentDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}

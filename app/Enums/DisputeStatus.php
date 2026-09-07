<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Resolved = 'resolved';

    public function isActive(): bool
    {
        return $this !== self::Resolved;
    }
}

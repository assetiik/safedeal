<?php

namespace App\Support;

use App\Models\Deal;

final class DealCode
{
    public static function format(Deal $deal): string
    {
        $year = $deal->created_at?->format('Y') ?? now()->format('Y');

        return sprintf('#SD-%s-%06d', $year, $deal->deal_number);
    }

    public static function short(Deal $deal): string
    {
        return '#'.$deal->deal_number;
    }
}

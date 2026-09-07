<?php

namespace App\Http\Support;

use Carbon\CarbonInterface;

final class ApiDate
{
    public static function iso(?CarbonInterface $date): ?string
    {
        return $date?->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }

    public static function date(?CarbonInterface $date): ?string
    {
        return $date?->format('Y-m-d');
    }
}

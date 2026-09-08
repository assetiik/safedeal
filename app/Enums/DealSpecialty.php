<?php

namespace App\Enums;

enum DealSpecialty: string
{
    case Design = 'Дизайн';
    case Development = 'Разработка';
    case Marketing = 'Маркетинг';
    case Lawyers = 'Юристы';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

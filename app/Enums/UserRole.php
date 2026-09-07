<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Contractor = 'contractor';
    case Admin = 'admin';

    public function isClient(): bool
    {
        return $this !== self::Admin;
    }

    public static function registrable(): array
    {
        return [self::Customer->value, self::Contractor->value];
    }
}

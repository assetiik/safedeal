<?php

namespace App\Enums;

enum DocumentType: string
{
    case Contract = 'contract';
    case Technical = 'technical';
    case Act = 'act';
    case Other = 'other';
}

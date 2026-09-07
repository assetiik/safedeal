<?php

namespace App\Models;

use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'deal_id',
        'type',
        'amount_tenge',
        'currency',
        'status',
        'provider',
        'provider_payment_id',
        'direction',
        'idempotency_key',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'status' => PaymentStatus::class,
            'direction' => PaymentDirection::class,
            'amount_tenge' => 'integer',
            'provider_payload' => 'array',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}

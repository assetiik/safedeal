<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasUuids;

    protected $fillable = [
        'deal_id',
        'template_version',
        'body_snapshot',
        'signature_type',
        'customer_confirmed_at',
        'contractor_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_confirmed_at' => 'datetime',
            'contractor_confirmed_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function bothConfirmed(): bool
    {
        return $this->customer_confirmed_at !== null && $this->contractor_confirmed_at !== null;
    }
}

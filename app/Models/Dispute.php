<?php

namespace App\Models;

use App\Enums\DisputeResolutionType;
use App\Enums\DisputeStatus;
use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasUuids;

    protected $fillable = [
        'deal_id',
        'status',
        'reason',
        'opened_by_user_id',
        'resolution_type',
        'resolution_note',
        'customer_amount_tenge',
        'contractor_amount_tenge',
        'resolved_by_admin_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisputeStatus::class,
            'resolution_type' => DisputeResolutionType::class,
            'customer_amount_tenge' => 'integer',
            'contractor_amount_tenge' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $dispute = $this->where($field ?? $this->getRouteKeyName(), $value)->first();

        if ($dispute === null) {
            throw ApiException::notFound('Спор не найден');
        }

        $user = auth()->user();
        $deal = $dispute->deal;

        if ($user instanceof User && ! $user->isAdmin() && ($deal === null || ! $deal->isParticipant($user))) {
            throw ApiException::notFound('Спор не найден');
        }

        return $dispute;
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_admin_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DisputeEvent::class);
    }
}

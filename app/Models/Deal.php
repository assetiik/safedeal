<?php

namespace App\Models;

use App\Enums\DealStatus;
use App\Enums\DealVisibility;
use App\Exceptions\ApiException;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'deal_number',
        'status',
        'visibility',
        'specialty',
        'title',
        'description',
        'amount_tenge',
        'currency',
        'commission_rate_bps',
        'commission_amount_tenge',
        'deadline',
        'terms',
        'additional_terms',
        'required_documents',
        'customer_user_id',
        'contractor_user_id',
        'contractor_invite_email',
        'customer_confirmed_contract',
        'contractor_confirmed_contract',
        'funds_frozen',
    ];

    protected function casts(): array
    {
        return [
            'status' => DealStatus::class,
            'visibility' => DealVisibility::class,
            'amount_tenge' => 'integer',
            'commission_rate_bps' => 'integer',
            'commission_amount_tenge' => 'integer',
            'deadline' => 'date',
            'required_documents' => 'array',
            'customer_confirmed_contract' => 'boolean',
            'contractor_confirmed_contract' => 'boolean',
            'funds_frozen' => 'boolean',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $deal = $this->where($field ?? $this->getRouteKeyName(), $value)->first();

        if ($deal === null) {
            throw ApiException::notFound('Сделка не найдена');
        }

        $user = auth()->user();

        if ($user instanceof User && ! $user->isAdmin() && ! $deal->isParticipant($user) && ! $deal->isClaimableBy($user)) {
            // Contractors may resolve public deals to claim or get "already taken".
            if (! ($user->isContractor() && $deal->visibility === DealVisibility::Public)) {
                throw ApiException::notFound('Сделка не найдена');
            }
        }

        return $deal;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isParticipant(User $user): bool
    {
        if ($this->customer_user_id === $user->id) {
            return true;
        }

        if ($this->contractor_user_id === $user->id) {
            return true;
        }

        return $user->isContractor()
            && filled($this->contractor_invite_email)
            && Str::lower($this->contractor_invite_email) === Str::lower($user->email);
    }

    public function isOpenOrder(): bool
    {
        return $this->visibility === DealVisibility::Public
            && $this->status === DealStatus::AwaitingExecutor
            && $this->contractor_user_id === null;
    }

    public function isClaimableBy(User $user): bool
    {
        return $user->isContractor() && $user->isActive() && $this->isOpenOrder();
    }

    public function reservedAmount(): int
    {
        return $this->amount_tenge - $this->commission_amount_tenge;
    }

    public function scopeOpenOrders(Builder $query): Builder
    {
        return $query
            ->where('visibility', DealVisibility::Public)
            ->where('status', DealStatus::AwaitingExecutor)
            ->whereNull('contractor_user_id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user): void {
            $inner->where('customer_user_id', $user->id)
                ->orWhere('contractor_user_id', $user->id)
                ->orWhere(function (Builder $invite) use ($user): void {
                    $invite->where('contractor_invite_email', Str::lower($user->email))
                        ->where('status', DealStatus::AwaitingExecutor);
                });
        });
    }
}

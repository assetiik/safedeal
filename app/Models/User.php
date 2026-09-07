<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
        'accepted_terms_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'accepted_terms_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->profile()->create([
                'display_name' => Str::before($user->email, '@'),
            ]);

            if ($user->role === UserRole::Contractor) {
                Deal::query()
                    ->whereNull('contractor_user_id')
                    ->where('contractor_invite_email', Str::lower($user->email))
                    ->where('status', \App\Enums\DealStatus::AwaitingExecutor)
                    ->update(['contractor_user_id' => $user->id]);
            }
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function customerDeals(): HasMany
    {
        return $this->hasMany(Deal::class, 'customer_user_id');
    }

    public function contractorDeals(): HasMany
    {
        return $this->hasMany(Deal::class, 'contractor_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isContractor(): bool
    {
        return $this->role === UserRole::Contractor;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function displayName(): string
    {
        return $this->profile?->display_name ?: Str::before($this->email, '@');
    }
}

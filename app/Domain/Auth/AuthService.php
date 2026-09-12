<?php

namespace App\Domain\Auth;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\ApiException;
use App\Mail\PasswordResetMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class AuthService
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function register(array $data): array
    {
        $user = User::query()->create([
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
            'status' => UserStatus::Active,
            'accepted_terms_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->audit->record(AuditAction::UserRegistered, $user, $user, payload: [
            'role' => $user->role->value,
        ]);

        return [
            'user' => $user->load('profile'),
            'tokens' => $this->tokens->issue($user),
        ];
    }

    /**
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', Str::lower($email))->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new ApiException('INVALID_CREDENTIALS', 'Неверный email или пароль', 401);
        }

        if (! $user->isActive()) {
            throw ApiException::blocked();
        }

        $this->audit->record(AuditAction::UserLoggedIn, $user, $user, payload: [
            'ip' => request()?->ip(),
        ]);

        return [
            'user' => $user->load('profile'),
            'tokens' => $this->tokens->issue($user),
        ];
    }

    public function forgotPassword(string $email): void
    {
        $user = User::query()->where('email', Str::lower($email))->first();

        if ($user === null) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetCode::query()->create([
            'email' => $user->email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('escrow.reset_code_ttl_minutes')),
        ]);

        Mail::to($user->email)->send(new PasswordResetMail($code));
    }

    public function resetPassword(string $email, string $code, string $password): void
    {
        $email = Str::lower($email);

        $record = PasswordResetCode::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($record === null || ! Hash::check($code, $record->code_hash)) {
            throw new ApiException('INVALID_RESET_CODE', 'Неверный или просроченный код', 400);
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            throw new ApiException('INVALID_RESET_CODE', 'Неверный или просроченный код', 400);
        }

        $user->update(['password' => $password]);
        $record->update(['used_at' => now()]);
        $user->tokens()->delete();
        $user->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function changePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw new ApiException('INVALID_PASSWORD', 'Текущий пароль указан неверно', 400);
        }

        if (Hash::check($new, $user->password)) {
            throw new ApiException('PASSWORD_UNCHANGED', 'Новый пароль должен отличаться от текущего', 400);
        }

        $user->update(['password' => $new]);
        $user->tokens()->where('id', '!=', optional($user->currentAccessToken())->id)->delete();
    }
}

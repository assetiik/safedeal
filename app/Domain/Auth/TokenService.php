<?php

namespace App\Domain\Auth;

use App\Exceptions\ApiException;
use App\Models\RefreshToken;
use App\Models\User;

final class TokenService
{
    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, token_type: string}
     */
    public function issue(User $user): array
    {
        $ttl = (int) config('escrow.access_token_ttl');
        $access = $user->createToken('access', ['*'], now()->addSeconds($ttl));
        $plainRefresh = bin2hex(random_bytes(32));

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainRefresh),
            'expires_at' => now()->addSeconds((int) config('escrow.refresh_token_ttl')),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $plainRefresh,
            'expires_in' => $ttl,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, token_type: string}
     */
    public function rotate(string $refreshToken): array
    {
        $record = RefreshToken::query()
            ->where('token_hash', hash('sha256', $refreshToken))
            ->first();

        if ($record === null || ! $record->isValid()) {
            throw new ApiException('INVALID_REFRESH_TOKEN', 'Недействительный refresh-токен', 401);
        }

        $user = $record->user;
        $record->update(['revoked_at' => now()]);

        $user->tokens()->where('name', 'access')->delete();

        return $this->issue($user);
    }

    public function revokeCurrent(?User $user, ?string $refreshToken = null): void
    {
        if ($user === null) {
            return;
        }

        $current = $user->currentAccessToken();
        if ($current) {
            $current->delete();
        }

        if ($refreshToken) {
            RefreshToken::query()
                ->where('user_id', $user->id)
                ->where('token_hash', hash('sha256', $refreshToken))
                ->update(['revoked_at' => now()]);
        }
    }
}

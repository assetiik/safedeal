<?php

namespace App\Domain\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    public function record(
        AuditAction $action,
        Model $entity,
        ?User $actor = null,
        ?string $dealId = null,
        array $payload = [],
    ): AuditLog {
        $actor ??= auth()->user();

        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'action' => $action->value,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'deal_id' => $dealId,
            'payload' => $payload,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}

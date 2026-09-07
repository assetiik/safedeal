<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_user_id' => $this->actor_user_id,
            'actor_role' => $this->actor_role?->value,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'deal_id' => $this->deal_id,
            'payload' => $this->payload ?? (object) [],
            'ip' => $this->ip,
            'created_at' => ApiDate::iso($this->created_at),
        ];
    }
}

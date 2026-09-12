<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Dispute */
class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deal = $this->relationLoaded('deal') ? $this->deal : null;

        return [
            'id' => $this->id,
            'deal_id' => $this->deal_id,
            'deal_number' => $deal?->deal_number,
            'deal_title' => $deal?->title,
            'customer_name' => $deal?->customer?->displayName(),
            'contractor_name' => $deal?->contractor?->displayName()
                ?? (filled($deal?->contractor_invite_email) ? $deal->contractor_invite_email : null),
            'status' => $this->status->value,
            'reason' => $this->reason,
            'opened_by_user_id' => $this->opened_by_user_id,
            'resolution_type' => $this->resolution_type?->value,
            'resolution_note' => $this->resolution_note,
            'customer_amount_tenge' => $this->customer_amount_tenge,
            'contractor_amount_tenge' => $this->contractor_amount_tenge,
            'resolved_at' => ApiDate::iso($this->resolved_at),
            'amount_tenge' => $deal?->amount_tenge,
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($event) => [
                'id' => $event->id,
                'type' => $event->type,
                'actor_user_id' => $event->actor_user_id,
                'payload' => $event->payload,
                'created_at' => ApiDate::iso($event->created_at),
            ])->values()),
            'created_at' => ApiDate::iso($this->created_at),
            'updated_at' => ApiDate::iso($this->updated_at),
        ];
    }
}

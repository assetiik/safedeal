<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Deal */
class DealListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $counterparty = $user?->isCustomer()
            ? ($this->contractor?->displayName() ?? $this->contractor_invite_email)
            : $this->customer?->displayName();

        return [
            'id' => $this->id,
            'deal_number' => $this->deal_number,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->labelRu(),
            'visibility' => $this->visibility?->value ?? 'private',
            'specialty' => $this->specialty,
            'amount_tenge' => $this->amount_tenge,
            'currency' => $this->currency,
            'counterparty_name' => $counterparty,
            'updated_at' => ApiDate::iso($this->updated_at),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deal_id' => $this->deal_id,
            'deal_number' => $this->whenLoaded('deal', fn () => $this->deal?->deal_number),
            'type' => $this->type->value,
            'amount_tenge' => $this->amount_tenge,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'provider' => $this->provider,
            'provider_payment_id' => $this->provider_payment_id,
            'direction' => $this->direction->value,
            'created_at' => ApiDate::iso($this->created_at),
            'updated_at' => ApiDate::iso($this->updated_at),
        ];
    }
}

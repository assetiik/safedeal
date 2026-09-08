<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Deal */
class OpenOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'customer_name' => $this->customer?->displayName(),
            'specialty' => $this->specialty,
            'amount_tenge' => $this->amount_tenge,
            'deadline' => ApiDate::date($this->deadline),
            'city' => 'Онлайн',
            'published_at' => ApiDate::iso($this->created_at),
            'created_at' => ApiDate::iso($this->created_at),
        ];
    }
}

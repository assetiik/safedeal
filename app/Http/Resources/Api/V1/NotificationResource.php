<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AppNotification */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category->value,
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload ?? (object) [],
            'read_at' => ApiDate::iso($this->read_at),
            'created_at' => ApiDate::iso($this->created_at),
        ];
    }
}

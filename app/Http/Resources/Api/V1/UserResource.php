<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'email_verified_at' => ApiDate::iso($this->email_verified_at),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'created_at' => ApiDate::iso($this->created_at),
        ];
    }
}

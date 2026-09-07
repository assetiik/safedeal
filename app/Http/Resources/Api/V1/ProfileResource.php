<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Profile */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'display_name' => $this->display_name,
            'tax_id' => $this->tax_id,
            'phone' => $this->phone,
            'bank_details' => $this->bank_details,
            'legal_address' => $this->legal_address,
            'contact_person' => $this->contact_person,
            'updated_at' => ApiDate::iso($this->updated_at),
        ];
    }
}

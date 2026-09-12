<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Support\ApiDate;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deal_id' => $this->deal_id,
            'deal_number' => $this->deal?->deal_number,
            'type' => $this->type->value,
            'title' => $this->title,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'uploaded_by_name' => $this->uploadedBy?->displayName(),
            'created_at' => ApiDate::iso($this->created_at),
        ];
    }
}

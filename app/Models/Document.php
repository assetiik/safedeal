<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'deal_id',
        'type',
        'title',
        'file_name',
        'mime_type',
        'size_bytes',
        'storage_key',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'size_bytes' => 'integer',
        ];
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $document = $this->where($field ?? $this->getRouteKeyName(), $value)->first();

        if ($document === null) {
            throw ApiException::notFound('Документ не найден');
        }

        $user = auth()->user();
        $deal = $document->deal;

        if ($user instanceof User && ! $user->isAdmin() && ($deal === null || ! $deal->isParticipant($user))) {
            throw ApiException::notFound('Документ не найден');
        }

        return $document;
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}

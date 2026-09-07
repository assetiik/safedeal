<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'display_name',
        'tax_id',
        'phone',
        'bank_details',
        'legal_address',
        'contact_person',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

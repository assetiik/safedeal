<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DealStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(DealStatus::class)],
            'reason' => ['required', 'string', 'min:3'],
        ];
    }
}

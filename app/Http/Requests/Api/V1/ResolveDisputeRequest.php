<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DisputeResolutionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_type' => ['required', Rule::enum(DisputeResolutionType::class)],
            'resolution_note' => ['required', 'string', 'min:3'],
            'customer_amount_tenge' => ['nullable', 'integer', 'min:0'],
            'contractor_amount_tenge' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

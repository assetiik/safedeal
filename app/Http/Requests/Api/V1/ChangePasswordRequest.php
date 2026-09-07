<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $min = (int) config('escrow.password_min', 8);

        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:'.$min, 'different:current_password'],
        ];
    }
}

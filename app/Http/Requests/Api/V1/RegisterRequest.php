<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => Str::lower($this->string('email')->toString())]);
        }
    }

    public function rules(): array
    {
        $min = (int) config('escrow.password_min', 8);

        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:'.$min],
            'role' => ['required', Rule::in(UserRole::registrable())],
            'accepted_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'accepted_terms.accepted' => 'Необходимо принять условия использования',
            'role.in' => 'Роль должна быть customer или contractor',
        ];
    }
}

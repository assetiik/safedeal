<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DealSpecialty;
use App\Enums\DealVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $visibility = $this->input('visibility');

        if ($visibility === null && $this->boolean('is_open')) {
            $visibility = DealVisibility::Public->value;
        }

        if ($visibility === null) {
            $visibility = DealVisibility::Private->value;
        }

        $merge = ['visibility' => $visibility];

        if ($this->filled('executor_email')) {
            $merge['executor_email'] = Str::lower($this->string('executor_email')->toString());
        } else {
            $merge['executor_email'] = null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'visibility' => ['required', Rule::enum(DealVisibility::class)],
            'executor_email' => [
                'exclude_if:visibility,'.DealVisibility::Public->value,
                'required',
                'email',
            ],
            'specialty' => [
                'nullable',
                'string',
                'required_if:visibility,'.DealVisibility::Public->value,
                Rule::in(DealSpecialty::values()),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount_tenge' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'terms' => ['required', 'string'],
            'additional_terms' => ['nullable', 'string'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string', 'max:255'],
            'is_open' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'executor_email.required' => 'The executor email field is required.',
            'executor_email.required_if' => 'The executor email field is required.',
            'specialty.required_if' => 'Укажите специализацию для открытого заказа',
            'specialty.in' => 'Некорректная специализация',
        ];
    }
}

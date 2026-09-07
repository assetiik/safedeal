<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CreateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('executor_email')) {
            $this->merge(['executor_email' => Str::lower($this->string('executor_email')->toString())]);
        }
    }

    public function rules(): array
    {
        return [
            'executor_email' => ['required', 'email'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount_tenge' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'terms' => ['required', 'string'],
            'additional_terms' => ['nullable', 'string'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string', 'max:255'],
        ];
    }
}

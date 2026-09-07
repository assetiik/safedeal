<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = (int) config('escrow.documents.max_kb', 20480);
        $mimes = implode(',', config('escrow.documents.mimes', ['pdf']));

        return [
            'file' => ['required', 'file', 'max:'.$max, 'mimes:'.$mimes],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}

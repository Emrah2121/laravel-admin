<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string'],
            'value' => ['nullable', 'array'],
        ];
    }
}

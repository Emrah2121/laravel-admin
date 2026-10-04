<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('languages', 'name')->ignore($this->language->id)],
            'code' => ['required', 'string', 'max:2', Rule::unique('languages', 'code')->ignore($this->language->id)],
            'default' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ];
    }
}

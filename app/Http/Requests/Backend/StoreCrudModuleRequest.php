<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrudModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:40'],
            'migrate' => ['nullable', 'boolean'],
            'seed' => ['nullable', 'boolean'],
        ];
    }
}

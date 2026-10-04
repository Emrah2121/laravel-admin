<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20', 'unique:languages,name'],
            'code' => ['required', 'string', 'max:2', 'unique:languages,code'],
            'default' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ];
    }
}

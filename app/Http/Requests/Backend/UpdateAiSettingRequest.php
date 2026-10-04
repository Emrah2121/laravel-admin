<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'gemini_model' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9.\-]+$/'],
            'pexels_api_key' => ['nullable', 'string', 'max:500'],
        ];
    }
}

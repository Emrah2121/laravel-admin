<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            [
                'name' => ['required', 'string', 'max:120', Rule::unique('settings', 'name')->ignore($this->setting->id)],
                'slug' => ['nullable', 'string', 'max:255'],
            ],
        ];

        foreach (active_langs() as $lang) {
            $rules[] = [
                'content:' . $lang->code => ['nullable', 'string'],
            ];
        }

        return Arr::collapse($rules);
    }
}

<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            [
                'name' => ['required', 'string', 'max:120', 'unique:settings,name'],
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

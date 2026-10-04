<?php

namespace App\Http\Requests\Backend;

use App\Rules\ValidImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (active_langs() as $lang) {
            $rules[] = [
                'title:' . $lang->code => ['required', 'string', 'max:255'],
                'subtitle:' . $lang->code => ['nullable', 'string', 'max:255'],
                'content:' . $lang->code => ['required', 'string'],
            ];
        }

        $rules[] = [
            'slug' => ['required', 'string', 'max:255', Rule::unique('blogs', 'slug')],
            'publish_date' => ['required', 'date'],
            'order' => ['nullable', 'integer'],
            'status' => ['required', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', new ValidImageMimeTypeRule()],
        ];

        return Arr::collapse($rules);
    }

    public function attributes(): array
    {
        return [
            'images.*' => trans('backend.labels.images'),
        ];
    }
}

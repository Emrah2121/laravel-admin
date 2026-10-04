<?php

namespace App\Http\Requests\Backend;

use App\Rules\ValidImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
                'description:' . $lang->code => ['nullable', 'string'],
            ];
        }

        $rules[] = [
            'slug' => ['required', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($this->service->id)],
            'order' => ['nullable', 'integer'],
            'status' => ['required', 'boolean'],
            'image' => ['nullable', 'array'],
            'image.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', new ValidImageMimeTypeRule()],
        ];

        return Arr::collapse($rules);
    }

    public function attributes(): array
    {
        return [
            'image.*' => trans('backend.labels.services_image'),
        ];
    }
}

<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::lower($this->name),
            'guard_name' => Str::lower($this->guard_name),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:100', 'unique:permissions,name'],
            'guard_name' => ['required', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('backend.labels.name'),
            'guard_name' => trans('backend.labels.guard_name'),
        ];
    }
}

<?php

namespace App\Http\Requests\Backend;

use App\Rules\ValidImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:30', 'regex:/^([^0-9]*)$/'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[\W]/'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', 'in:0,1'],
            'image' => ['nullable'],
            'image.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', new ValidImageMimeTypeRule()],
        ];
    }

    public function attributes(): array
    {
        return [
            'image.*' => trans('backend.labels.image'),
        ];
    }
}

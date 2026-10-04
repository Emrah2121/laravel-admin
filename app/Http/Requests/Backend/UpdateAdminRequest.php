<?php

namespace App\Http\Requests\Backend;

use App\Rules\ValidImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:30', 'regex:/^([^0-9]*)$/'],
            'email' => ['required', 'email', 'max:40', Rule::unique('admins')->ignore($this->admin->id)],
            'password' => ['nullable', 'string', 'min:8'],
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

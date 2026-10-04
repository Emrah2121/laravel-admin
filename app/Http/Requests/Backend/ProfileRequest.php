<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^([^0-9]*)$/|max:40',
            'email' => 'required|email|max:50',
            'password' => 'nullable|min:5',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('backend.labels.name'),
            'email' => trans('backend.labels.email'),
            'password' => trans('backend.labels.password'),
        ];
    }
}

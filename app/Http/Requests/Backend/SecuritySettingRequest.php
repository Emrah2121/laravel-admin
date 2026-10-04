<?php

namespace App\Http\Requests\Backend;

use App\Models\SecuritySetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SecuritySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return admin()->isDeveloper();
    }

    public function rules(): array
    {
        $security = SecuritySetting::current();

        return [
            'rate_limit_status' => 'required|boolean',
            'recaptcha_status' => 'required|boolean',
            'recaptcha_site_key' => [
                Rule::requiredIf($this->boolean('recaptcha_status') && blank($security->recaptcha_site_key)),
                'nullable',
                'string',
                'max:255',
            ],
            'recaptcha_secret_key' => [
                Rule::requiredIf($this->boolean('recaptcha_status') && blank($security->recaptcha_secret_key)),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'rate_limit_status' => trans('backend.labels.rate_limit'),
            'recaptcha_status' => trans('backend.labels.recaptcha'),
            'recaptcha_site_key' => trans('backend.labels.recaptcha_site_key'),
            'recaptcha_secret_key' => trans('backend.labels.recaptcha_secret_key'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('backend.profile.form') . '#kt_profile_security';
    }
}

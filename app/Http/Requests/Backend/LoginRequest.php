<?php

namespace App\Http\Requests\Backend;

use App\Models\SecuritySetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    private ?SecuritySetting $security = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        if ($this->security()->isRecaptchaActive()) {
            $rules['g-recaptcha-response'] = [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$this->security()->passesRecaptcha($value, $this->ip())) {
                        $fail(trans('backend.messages.error.recaptcha'));
                    }
                },
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'g-recaptcha-response' => trans('backend.labels.recaptcha'),
        ];
    }

    public function registerFailedAttempt(): void
    {
        $this->security()->registerFailedLogin($this->ip());
    }

    public function clearFailedAttempts(): void
    {
        $this->security()->clearFailedLogins($this->ip());
    }

    protected function prepareForValidation(): void
    {
        $retryAfter = $this->security()->loginRetryAfter($this->ip());

        if ($retryAfter > 0) {
            throw new HttpResponseException(
                redirect()
                    ->route('backend.login.form')
                    ->withWarning(trans('backend.messages.warning.too_many_attempts', ['seconds' => $retryAfter]))
            );
        }
    }

    private function security(): SecuritySetting
    {
        return $this->security ??= SecuritySetting::current();
    }
}

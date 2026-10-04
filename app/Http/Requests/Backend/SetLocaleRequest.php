<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class SetLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = active_langs()->pluck('code')->toArray();

        return [
            'lang' => ['required', 'string', 'in:' . implode(',', $allowed)],
        ];
    }
}

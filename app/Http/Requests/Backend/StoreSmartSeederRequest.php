<?php

namespace App\Http\Requests\Backend;

use App\Services\SmartSeeder\SmartSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmartSeederRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string', Rule::in(array_keys(SmartSeeder::make()->modules()->all()))],
            'count' => ['required', 'integer', 'between:1,' . SmartSeeder::MAX_RECORDS],
            'instruction' => ['required', 'string', 'max:1000'],
        ];
    }
}

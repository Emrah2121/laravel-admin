<?php

namespace App\Http\Requests\Backend;

use App\Services\CrudGenerator\CrudGenerator;
use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\ModuleSpec;
use App\Services\CrudGenerator\SpecValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PreviewCrudModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'plural' => ['nullable', 'string', 'max:50'],
            'label' => ['nullable', 'string', 'max:60'],
            'icon' => ['nullable', 'string', 'max:40'],
            'slug' => ['required', 'boolean'],
            'publish_date' => ['required', 'boolean'],
            'order' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
            'fields' => ['required', 'array', 'min:1', 'max:' . SpecValidator::MAX_FIELDS],
            'fields.*.name' => ['required', 'string', 'max:64'],
            'fields.*.label' => ['nullable', 'string', 'max:60'],
            'fields.*.type' => ['required', Rule::in(FieldSpec::TYPES)],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.translatable' => ['required', 'boolean'],
            'fields.*.quill' => ['required', 'boolean'],
            'fields.*.max_length' => ['nullable', 'integer', 'between:1,255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $errors = CrudGenerator::make()->validate($this->spec());

                foreach ($errors as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            },
        ];
    }

    public function spec(): ModuleSpec
    {
        return ModuleSpec::fromArray($this->validated());
    }
}

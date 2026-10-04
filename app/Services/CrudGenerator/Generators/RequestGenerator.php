<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;

class RequestGenerator extends StubGenerator
{
    public function operations(ModuleSpec $spec): array
    {
        return [
            FileOperation::create(
                "app/Http/Requests/Backend/{$spec->storeRequest()}.php",
                $this->request($spec, $spec->storeRequest(), false)
            ),
            FileOperation::create(
                "app/Http/Requests/Backend/{$spec->updateRequest()}.php",
                $this->request($spec, $spec->updateRequest(), true)
            ),
        ];
    }

    private function request(ModuleSpec $spec, string $class, bool $update): string
    {
        $translationRules = array_map(
            fn (FieldSpec $field) => "'{$field->name}:' . \$lang->code => " . $this->rules($field) . ',',
            $spec->translatableFields()
        );

        $mainRules = [];

        if ($spec->hasSlug) {
            $unique = "Rule::unique('{$spec->table()}', 'slug')" . ($update ? "->ignore(\$this->{$spec->routeParameter()}->id)" : '');
            $mainRules[] = "'slug' => ['required', 'string', 'max:255', {$unique}],";
        }

        if ($spec->hasPublishDate) {
            $mainRules[] = "'publish_date' => ['required', 'date'],";
        }

        foreach ($spec->mainFields() as $field) {
            $mainRules[] = "'{$field->name}' => " . $this->rules($field) . ',';
        }

        if ($spec->hasOrder) {
            $mainRules[] = "'order' => ['nullable', 'integer'],";
        }

        if ($spec->hasStatus) {
            $mainRules[] = "'status' => ['required', 'boolean'],";
        }

        $attributes = [];

        foreach ($spec->imageFields() as $field) {
            $presence = $field->required && !$update ? 'required' : 'nullable';
            $mainRules[] = "'{$field->name}' => ['{$presence}', 'array'],";
            $mainRules[] = "'{$field->name}.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048', new ValidImageMimeTypeRule()],";
            $attributes[] = "'{$field->name}.*' => trans('backend.labels.{$spec->labelKey($field)}'),";
        }

        return $this->render('request', $spec, [
            'class' => $class,
            'translation_rules' => $this->lines($translationRules, 16),
            'main_rules' => $this->lines($mainRules, 12),
            'attributes' => $this->lines($attributes, 12),
        ]);
    }

    private function rules(FieldSpec $field): string
    {
        $rules = [$field->required ? 'required' : 'nullable', 'string'];

        if ($field->isString()) {
            $rules[] = "max:{$field->maxLength}";
        }

        return "['" . implode("', '", $rules) . "']";
    }
}

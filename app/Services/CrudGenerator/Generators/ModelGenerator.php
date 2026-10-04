<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;
use Illuminate\Support\Str;

class ModelGenerator extends StubGenerator
{
    public function operations(ModuleSpec $spec): array
    {
        $casts = $spec->hasPublishDate
            ? "\nprotected \$casts = [\n    'publish_date' => 'date',\n];"
            : '';

        $scopes = [];

        if ($spec->hasStatus) {
            $scopes[] = "public function scopeActive(\$query)\n{\n    return \$query->where('status', 1);\n}\n";
        }

        if ($spec->hasOrder) {
            $scopes[] = "public function scopeOrdered(\$query)\n{\n    return \$query->orderBy('order', 'asc');\n}\n";
        }

        $accessors = array_map(
            fn (FieldSpec $field) => "\npublic function get" . Str::studly($field->name) . "ImagesAttribute()\n{\n    return \$this->getDocuments('{$spec->imageCollection($field)}');\n}",
            $spec->imageFields()
        );

        $mainQuill = array_filter($spec->mainFields(), fn (FieldSpec $field) => $field->quill);
        $translationQuill = array_filter($spec->translatableFields(), fn (FieldSpec $field) => $field->quill);

        return [
            FileOperation::create("app/Models/{$spec->name}.php", $this->render('model', $spec, [
                'casts' => $this->lines([$casts], 4),
                'scopes' => $this->lines($scopes, 4),
                'accessors' => $this->lines($accessors, 4),
                'mutators' => $this->lines($this->mutators($mainQuill), 4),
            ])),
            FileOperation::create("app/Models/{$spec->translationModel()}.php", $this->render('model.translation', $spec, [
                'translation_mutators' => $this->lines($this->mutators($translationQuill), 4),
            ])),
        ];
    }

    /**
     * Rich text is user HTML: it is sanitized before it reaches the database.
     *
     * @param  FieldSpec[]  $fields
     */
    private function mutators(array $fields): array
    {
        return array_values(array_map(
            fn (FieldSpec $field) => "\npublic function set" . Str::studly($field->name) . "Attribute(\$value)\n{\n    \$this->attributes['{$field->name}'] = sanitize_html(\$value);\n}",
            $fields
        ));
    }
}

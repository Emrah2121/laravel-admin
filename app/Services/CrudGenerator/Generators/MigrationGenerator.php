<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;

class MigrationGenerator extends StubGenerator
{
    public function operations(ModuleSpec $spec): array
    {
        $timestamp = now();

        $main = [];

        if ($spec->hasSlug) {
            $main[] = "\$table->string('slug')->unique();";
        }

        if ($spec->hasPublishDate) {
            $main[] = "\$table->date('publish_date')->nullable();";
        }

        foreach ($spec->mainFields() as $field) {
            $main[] = $this->column($field);
        }

        if ($spec->hasOrder) {
            $main[] = "\$table->unsignedInteger('order')->default(1);";
        }

        if ($spec->hasStatus) {
            $main[] = "\$table->boolean('status')->default(true);";
        }

        $translations = array_map(fn (FieldSpec $field) => $this->column($field), $spec->translatableFields());

        return [
            FileOperation::create(
                'database/migrations/' . $timestamp->format('Y_m_d_His') . "_create_{$spec->table()}_table.php",
                $this->render('migration.create', $spec, ['main_columns' => $this->lines($main, 12)])
            ),
            FileOperation::create(
                'database/migrations/' . $timestamp->copy()->addSecond()->format('Y_m_d_His') . "_create_{$spec->translationTable()}_table.php",
                $this->render('migration.translations', $spec, ['translation_columns' => $this->lines($translations, 12)])
            ),
        ];
    }

    private function column(FieldSpec $field): string
    {
        if ($field->isTextarea()) {
            // Blog keeps rich text nullable even when the form requires it.
            return '$table->' . ($field->quill ? 'longText' : 'text') . "('{$field->name}')->nullable();";
        }

        $length = $field->maxLength === FieldSpec::DEFAULT_MAX_LENGTH ? '' : ", {$field->maxLength}";

        return "\$table->string('{$field->name}'{$length})" . ($field->required ? '' : '->nullable()') . ';';
    }
}

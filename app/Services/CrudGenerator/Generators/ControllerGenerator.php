<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;

class ControllerGenerator extends StubGenerator
{
    public function operations(ModuleSpec $spec): array
    {
        $variable = '$' . $spec->variable();
        $hasImages = (bool) $spec->imageFields();

        $translationData = array_map(
            fn (FieldSpec $field) => "'{$field->name}' => \$data['{$field->name}:' . \$lang->code]" . ($field->required ? '' : ' ?? null') . ',',
            $spec->translatableFields()
        );

        $imageUploads = array_map(
            fn (FieldSpec $field) => "\nif (\$request->hasFile('{$field->name}')) {\n    \$uploadDocumentService->upload('{$spec->morphAlias()}', '{$field->name}', {$variable}->id, '{$spec->imageCollection($field)}', true, \$request);\n}",
            $spec->imageFields()
        );

        $imageDeletes = array_map(
            fn (FieldSpec $field) => "foreach ({$variable}->getDocuments('{$spec->imageCollection($field)}') as \$file) {\n    Storage::disk('documents')->delete(\$file->getAttributes()['document']);\n    \$file->delete();\n}\n",
            $spec->imageFields()
        );

        $extraColumns = [];

        if ($spec->hasStatus) {
            $extraColumns[] = "->addColumn('status', fn (\$row) => badge(\$row->status))";
        }

        if ($spec->hasPublishDate) {
            $extraColumns[] = "->addColumn('publish_date', fn (\$row) => is_object(\$row->publish_date) ? \$row->publish_date->format('Y-m-d') : \$row->publish_date)";
        }

        $rawColumns = array_values(array_diff($spec->datatableColumns(), ['publish_date']));

        $editLink = "route('backend.{$spec->key()}.edit', ['{$spec->routeParameter()}' => \$row->id])";

        return [
            FileOperation::create("app/Http/Controllers/Backend/{$spec->controller()}.php", $this->render('controller', $spec, [
                'with_relations' => $hasImages ? "['translation', 'files']" : "['translation']",
                'upload_parameter' => $hasImages ? ', UploadDocumentService $uploadDocumentService' : '',
                'main_data_store' => $this->lines($this->mainData($spec, false), 16),
                'main_data_update' => $this->lines($this->mainData($spec, true), 16),
                'translation_data' => $this->lines($translationData, 20),
                'translation_data_update' => $this->lines($translationData, 24),
                'image_uploads' => $this->lines($imageUploads, 12) . ($hasImages ? "\n" : ''),
                'image_deletes' => $this->lines($imageDeletes, 12),
                'table_cover' => $hasImages
                    ? $this->lines(["\$src = \$row->cover_image ?: (\$row->first_image ?: asset('backend/img/noimage.jpg'));"], 16)
                    : '',
                'datatable_columns' => $hasImages
                    ? $this->lines([
                        "<a href=\"' . {$editLink} . '\" class=\"symbol symbol-50px\">",
                        "    <span class=\"symbol-label\" style=\"background-image:url(' . \$src . ');\"></span>",
                        '</a>',
                    ], 20)
                    : '',
                'datatable_extra_columns' => $this->lines($extraColumns, 12),
                'datatable_raw_columns' => $this->phpList($rawColumns),
            ])),
        ];
    }

    private function mainData(ModuleSpec $spec, bool $update): array
    {
        $data = [];

        if ($spec->hasSlug) {
            $data[] = "'slug' => \$data['slug'],";
        }

        if ($spec->hasStatus) {
            $data[] = "'status' => \$data['status'],";
        }

        if ($spec->hasPublishDate) {
            $data[] = "'publish_date' => \$data['publish_date'],";
        }

        foreach ($spec->mainFields() as $field) {
            $data[] = "'{$field->name}' => \$data['{$field->name}']" . ($field->required ? '' : ' ?? null') . ',';
        }

        if ($spec->hasOrder) {
            $data[] = "'order' => \$data['order'] ?? " . ($update ? '$' . $spec->variable() . '->order' : '1') . ',';
        }

        return $data;
    }
}

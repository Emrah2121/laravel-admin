<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;

class ViewGenerator extends StubGenerator
{
    public function operations(ModuleSpec $spec): array
    {
        $directory = "resources/views/backend/{$spec->key()}";

        return [
            FileOperation::create("{$directory}/index.blade.php", $this->render('views.index', $spec, [
                'datatable_view_columns' => $this->phpList($spec->datatableColumns()),
            ])),
            FileOperation::create("{$directory}/tables/index.blade.php", $this->render('views.tables.index', $spec, [
                'table_headings' => $this->lines($this->tableHeadings($spec), 16),
            ])),
            FileOperation::create("{$directory}/form.blade.php", $this->form($spec)),
            FileOperation::create("{$directory}/show.blade.php", $this->render('views.show', $spec, [])),
            FileOperation::create("{$directory}/tables/show.blade.php", $this->show($spec)),
        ];
    }

    private function tableHeadings(ModuleSpec $spec): array
    {
        $headings = [];

        if ($spec->hasStatus) {
            $headings[] = "<th class=\"min-w-100px\">@lang('backend.labels.status')</th>";
        }

        if ($spec->hasPublishDate) {
            $headings[] = "<th class=\"min-w-120px\">@lang('backend.labels.date')</th>";
        }

        return $headings;
    }

    private function form(ModuleSpec $spec): string
    {
        $variable = '$' . $spec->variable();
        $mainTextareas = array_values(array_filter($spec->mainFields(), fn (FieldSpec $field) => $field->isTextarea()));

        $mainCards = $mainTextareas ? $this->lines([
            '',
            '<div class="card card-flush py-4">',
            '    <div class="card-header">',
            '        <div class="card-title">',
            "            <h2>@lang('backend.labels.general')</h2>",
            '        </div>',
            '    </div>',
            '    <div class="card-body pt-0">',
            $this->lines(array_map(fn (FieldSpec $field) => $this->input($spec, $field, false), $mainTextareas), 8),
            '    </div>',
            '</div>',
        ], 24) : '';

        $mediaCards = $spec->imageFields() ? $this->lines([
            '',
            '@if ($edit)',
            '    <div class="card card-flush py-4">',
            '        <div class="card-header">',
            '            <div class="card-title">',
            "                <h2>@lang('backend.labels.documents')</h2>",
            '            </div>',
            '        </div>',
            '        <div class="card-body pt-0">',
            "            @include('backend.includes.media', [",
            "                'files' => {$variable}->files,",
            "                'media_collection_name' => '{$spec->morphAlias()}_images',",
            "                'isDeleted' => true,",
            "                'isCovered' => true,",
            '            ])',
            '        </div>',
            '    </div>',
            '@endif',
            '',
        ], 24) : '';

        return $this->render('views.form', $spec, [
            'styles' => $spec->quillFields() ? $this->lines(['', '.ql-container {', '    min-height: 250px;', '}'], 8) : '',
            'image_cards' => $this->lines(array_map(fn (FieldSpec $field) => $this->imageCard($spec, $field), $spec->imageFields()), 24),
            'base_cards' => $this->lines($this->baseCards($spec), 24),
            'translation_inputs' => $this->lines(array_map(fn (FieldSpec $field) => $this->input($spec, $field, true), $spec->translatableFields()), 44),
            'main_cards' => $mainCards,
            'media_cards' => $mediaCards,
            'scripts' => $this->lines($this->scripts($spec), 12),
            'submit_scripts' => $spec->quillFields() ? $this->lines([
                'Object.entries(quills).forEach(([name, quill]) => {',
                '    let contentHtml = quill.root.innerHTML;',
                "    $('[name=\"' + name + '\"]').val(contentHtml === '<p><br></p>' ? '' : contentHtml);",
                '});',
                '',
            ], 16) : '',
        ]);
    }

    private function imageCard(ModuleSpec $spec, FieldSpec $field): string
    {
        $variable = '$' . $spec->variable();
        $collection = $spec->imageCollection($field);

        return implode("\n", [
            '<div class="card card-flush py-4">',
            '    <div class="card-header">',
            '        <div class="card-title">',
            '            <h2' . ($field->required ? ' class="required"' : '') . ">@lang('backend.labels.{$spec->labelKey($field)}')</h2>",
            '        </div>',
            '    </div>',
            '    <div class="card-body text-center pt-0">',
            '        <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3"',
            '            data-kt-image-input="true">',
            '            <div class="image-input-wrapper w-150px h-150px"',
            "                style=\"background-image: url('{{ isset({$variable}) && {$variable}->getDocument('{$collection}') ? asset({$variable}->getDocument('{$collection}')) : '' }}')\">",
            '            </div>',
            '            <label',
            '                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"',
            '                data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change image">',
            '                <i class="ki-outline ki-pencil fs-7"></i>',
            "                <input type=\"file\" name=\"{$field->name}[]\" multiple=\"multiple\"",
            '                    accept=".png, .jpg, .jpeg, .webp" />',
            '            </label>',
            '            <span',
            '                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"',
            '                data-kt-image-input-action="cancel"><i class="ki-outline ki-cross fs-2"></i></span>',
            '            <span',
            '                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"',
            '                data-kt-image-input-action="remove"><i class="ki-outline ki-cross fs-2"></i></span>',
            '        </div>',
            "        @error('{$field->name}')",
            '            <div class="invalid-feedback d-block">{{ $message }}</div>',
            '        @enderror',
            "        @error('{$field->name}.*')",
            '            <div class="invalid-feedback d-block">{{ $message }}</div>',
            '        @enderror',
            '    </div>',
            '</div>',
            '',
        ]);
    }

    private function baseCards(ModuleSpec $spec): array
    {
        $variable = '$' . $spec->variable();
        $alias = $spec->morphAlias();
        $cards = [];

        if ($spec->hasStatus) {
            $cards[] = implode("\n", [
                '<div class="card card-flush py-4">',
                '    <div class="card-header">',
                '        <div class="card-title">',
                "            <h2>@lang('backend.labels.status')</h2>",
                '        </div>',
                '        <div class="card-toolbar">',
                "            <div class=\"rounded-circle bg-success w-15px h-15px\" id=\"kt_{$alias}_status_indicator\">",
                '            </div>',
                '        </div>',
                '    </div>',
                '    <div class="card-body pt-0">',
                '        <select class="form-select mb-2" name="status" data-control="select2"',
                "            data-hide-search=\"true\" id=\"kt_{$alias}_status_select\">",
                '            <option value="1"',
                "                {{ (isset({$variable}) ? {$variable}->status : old('status', 1)) == 1 ? 'selected' : '' }}>",
                "                @lang('backend.labels.active')</option>",
                '            <option value="0"',
                "                {{ (isset({$variable}) ? {$variable}->status : old('status', 1)) == 0 ? 'selected' : '' }}>",
                "                @lang('backend.labels.passive')</option>",
                '        </select>',
                '    </div>',
                '</div>',
                '',
            ]);
        }

        $general = [];

        if ($spec->hasSlug) {
            $general[] = implode("\n", [
                '<div class="mb-7">',
                '    <label class="required form-label">Slug</label>',
                '    <input type="text" name="slug" required',
                "        class=\"form-control mb-2 @error('slug') is-invalid @enderror\"",
                "        value=\"{{ old('slug', {$variable}->slug ?? '') }}\" />",
                "    @error('slug')",
                '        <div class="invalid-feedback d-block">{{ $message }}</div>',
                '    @enderror',
                '</div>',
                '',
            ]);
        }

        if ($spec->hasPublishDate) {
            $general[] = implode("\n", [
                '<div class="mb-7">',
                "    <label class=\"required form-label\">@lang('backend.labels.date')</label>",
                "    <input class=\"form-control\" name=\"publish_date\" id=\"kt_{$alias}_datepicker\" required",
                "        value=\"{{ old('publish_date', isset({$variable}) ? {$variable}->publish_date?->format('Y-m-d') : date('Y-m-d')) }}\" />",
                "    @error('publish_date')",
                '        <div class="invalid-feedback d-block">{{ $message }}</div>',
                '    @enderror',
                '</div>',
                '',
            ]);
        }

        foreach ($spec->mainFields() as $field) {
            if ($field->isString()) {
                $general[] = $this->input($spec, $field, false);
            }
        }

        if ($spec->hasOrder) {
            $general[] = implode("\n", [
                '<div class="mb-2">',
                "    <label class=\"form-label\">@lang('backend.labels.order')</label>",
                '    <input type="number" name="order"',
                "        class=\"form-control mb-2 @error('order') is-invalid @enderror\"",
                "        value=\"{{ old('order', {$variable}->order ?? 1) }}\" />",
                "    @error('order')",
                '        <div class="invalid-feedback d-block">{{ $message }}</div>',
                '    @enderror',
                '</div>',
            ]);
        }

        if ($general) {
            $cards[] = implode("\n", [
                '<div class="card card-flush py-4">',
                '    <div class="card-header">',
                '        <div class="card-title">',
                "            <h2>@lang('backend.labels.general')</h2>",
                '        </div>',
                '    </div>',
                '    <div class="card-body pt-0">',
                $this->lines($general, 8),
                '    </div>',
                '</div>',
            ]);
        }

        return $cards;
    }

    /**
     * One form control. Translatable inputs are suffixed with ":{locale}" like Blog's.
     */
    private function input(ModuleSpec $spec, FieldSpec $field, bool $translatable): string
    {
        $variable = '$' . $spec->variable();
        $name = $translatable ? "{$field->name}:{{ \$lang->code }}" : $field->name;
        $oldKey = $translatable ? "'{$field->name}:' . \$lang->code" : "'{$field->name}'";
        $stored = $translatable
            ? "isset({$variable}) ? {$variable}->translate(\$lang->code)?->{$field->name} : ''"
            : "{$variable}->{$field->name} ?? ''";
        $value = "old({$oldKey}, {$stored})";
        $required = $field->required ? ' required' : '';
        $label = '<label class="' . ($field->required ? 'required ' : '') . "form-label\">@lang('backend.labels.{$spec->labelKey($field)}')</label>";
        $error = [
            "@error({$oldKey})",
            '    <div class="text-danger">{{ $message }}</div>',
            '@enderror',
        ];

        if ($field->quill) {
            $id = "kt_{$spec->morphAlias()}_{$field->name}" . ($translatable ? '_{{ $lang->code }}' : '');

            $control = [
                '    <div class="quill-wrapper">',
                "        <div id=\"{$id}\" class=\"min-h-300px mb-2\">",
                "            {!! sanitize_html({$value}) !!}",
                '        </div>',
                '    </div>',
                "    <textarea name=\"{$name}\"{$required} class=\"d-none\"></textarea>",
            ];
        } elseif ($field->isTextarea()) {
            $control = [
                "    <textarea name=\"{$name}\"{$required} rows=\"5\"",
                "        class=\"form-control mb-2\">{{ {$value} }}</textarea>",
            ];
        } else {
            $control = [
                "    <input type=\"text\" name=\"{$name}\"{$required} maxlength=\"{$field->maxLength}\"",
                '        class="form-control mb-2"',
                "        value=\"{{ {$value} }}\" />",
            ];
        }

        return implode("\n", array_merge(
            ['<div class="mb-2 fv-row">', '    ' . $label],
            $control,
            ['</div>'],
            $error,
        ));
    }

    private function scripts(ModuleSpec $spec): array
    {
        $alias = $spec->morphAlias();
        $scripts = [];

        if ($spec->hasPublishDate) {
            $scripts[] = "$(\"#kt_{$alias}_datepicker\").flatpickr({\n    dateFormat: \"Y-m-d\"\n});\n";
        }

        if ($spec->hasStatus) {
            $scripts[] = implode("\n", [
                "const statusSelect = document.getElementById('kt_{$alias}_status_select');",
                "const statusIndicator = document.getElementById('kt_{$alias}_status_indicator');",
                'const updateStatusIndicator = () => {',
                '    if (!statusSelect || !statusIndicator) return;',
                "    statusIndicator.classList.remove('bg-success', 'bg-danger');",
                "    statusIndicator.classList.add(statusSelect.value == 1 ? 'bg-success' : 'bg-danger');",
                '};',
                "$(statusSelect).on('change', updateStatusIndicator);",
                'updateStatusIndicator();',
                '',
            ]);
        }

        if (!$spec->quillFields()) {
            return $scripts;
        }

        $scripts[] = implode("\n", [
            'const quillOptions = {',
            '    modules: {',
            '        toolbar: [',
            '            [{',
            '                header: [1, 2, 3, false]',
            '            }],',
            "            ['bold', 'italic', 'underline', 'strike'],",
            '            [{',
            "                'list': 'ordered'",
            '            }, {',
            "                'list': 'bullet'",
            '            }],',
            '            [{',
            "                'align': []",
            '            }],',
            "            ['link', 'image', 'video'],",
            "            ['clean']",
            '        ]',
            '    },',
            "    theme: 'snow'",
            '};',
            '',
            'const quills = {};',
        ]);

        foreach ($spec->quillFields() as $field) {
            $id = "kt_{$alias}_{$field->name}";

            $scripts[] = $field->translatable
                ? implode("\n", [
                    '@foreach ($langs as $lang)',
                    "    quills['{$field->name}:{{ \$lang->code }}'] = new Quill('#{$id}_{{ \$lang->code }}', quillOptions);",
                    '@endforeach',
                ])
                : "quills['{$field->name}'] = new Quill('#{$id}', quillOptions);";
        }

        $scripts[] = '';

        return $scripts;
    }

    private function show(ModuleSpec $spec): string
    {
        $variable = '$' . $spec->variable();
        $hasImages = (bool) $spec->imageFields();

        $cover = $hasImages ? $this->lines([
            '<div class="card card-flush py-4">',
            '    <div class="card-header">',
            '        <div class="card-title">',
            "            <h2>@lang('backend.labels.image')</h2>",
            '        </div>',
            '    </div>',
            '    <div class="card-body text-center pt-0">',
            '        <div class="image-input image-input-outline image-input-placeholder mb-3">',
            '            <div class="image-input-wrapper w-150px h-150px"',
            "                 style=\"background-image: url('{{ {$variable}->cover_image ? asset({$variable}->cover_image) : ({$variable}->first_image ? asset({$variable}->first_image) : asset('backend/img/noimage.jpg')) }}');\">",
            '            </div>',
            '        </div>',
            '    </div>',
            '</div>',
            '',
        ], 8) : '';

        $details = [];

        if ($spec->hasStatus) {
            $details[] = $this->detail("@lang('backend.labels.status')", "{!! badge({$variable}->status) !!}");
        }

        if ($spec->hasSlug) {
            $details[] = $this->detail('Slug', "{{ {$variable}->slug }}");
        }

        if ($spec->hasPublishDate) {
            $details[] = $this->detail("@lang('backend.labels.date')", "{{ {$variable}->publish_date?->format('Y-m-d') ?? '—' }}");
        }

        if ($spec->hasOrder) {
            $details[] = $this->detail("@lang('backend.labels.order')", "{{ {$variable}->order }}");
        }

        $translations = array_map(
            fn (FieldSpec $field) => $this->showValue($spec, $field, "\$translations[\$lang->code]->{$field->name}"),
            $spec->translatableFields()
        );

        $mainFields = $spec->mainFields();
        $main = $mainFields ? $this->lines([
            '',
            '<div class="card card-flush py-4">',
            '    <div class="card-header">',
            '        <div class="card-title">',
            "            <h2>@lang('backend.labels.general')</h2>",
            '        </div>',
            '    </div>',
            '    <div class="card-body pt-0">',
            $this->lines(array_map(fn (FieldSpec $field) => $this->showValue($spec, $field, "{$variable}->{$field->name}"), $mainFields), 8),
            '    </div>',
            '</div>',
        ], 8) : '';

        $media = $hasImages ? $this->lines([
            '',
            '<div class="card card-flush py-4">',
            '    <div class="card-header">',
            '        <div class="card-title">',
            "            <h2>@lang('backend.labels.documents')</h2>",
            '        </div>',
            '    </div>',
            '    <div class="card-body pt-0">',
            "        @include('backend.includes.media', [",
            "            'files' => {$variable}->files,",
            "            'media_collection_name' => '{$spec->morphAlias()}_images',",
            "            'isDeleted' => false,",
            "            'isCovered' => false,",
            '        ])',
            '    </div>',
            '</div>',
        ], 8) : '';

        return $this->render('views.tables.show', $spec, [
            'show_cover' => $cover,
            'show_details' => $this->lines($details, 16),
            'show_translations' => $this->lines($translations, 36),
            'show_main' => $main,
            'show_media' => $media,
        ]);
    }

    private function detail(string $label, string $value): string
    {
        return implode("\n", [
            '<div class="mb-5">',
            "    <div class=\"fw-bold text-gray-800\">{$label}</div>",
            "    <div class=\"text-gray-600\">{$value}</div>",
            '</div>',
        ]);
    }

    /**
     * Rich text is sanitized on save, so it is printed raw like Blog's content.
     */
    private function showValue(ModuleSpec $spec, FieldSpec $field, string $expression): string
    {
        if ($field->quill) {
            $value = "<div class=\"text-gray-600 border p-5 rounded bg-light bg-opacity-50\">{!! {$expression} !!}</div>";
        } elseif ($field->isTextarea()) {
            $value = "<div class=\"text-gray-600\">{!! nl2br(e({$expression})) !!}</div>";
        } else {
            $value = "<div class=\"text-gray-800 fs-5 fw-bold\">{{ {$expression} }}</div>";
        }

        return implode("\n", [
            '<div class="mb-10">',
            "    <label class=\"fw-bold fs-6 mb-2\">@lang('backend.labels.{$spec->labelKey($field)}')</label>",
            "    {$value}",
            '</div>',
        ]);
    }
}

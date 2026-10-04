<?php

namespace App\Services\CrudGenerator;

final class StubRenderer
{
    /**
     * The only placeholders a stub may contain. Values are produced by the
     * generators from a validated spec, never copied from raw input.
     */
    public const PLACEHOLDERS = [
        'class',
        'translation_class',
        'table',
        'translation_table',
        'foreign_key',
        'key',
        'route_parameter',
        'variable',
        'morph_alias',
        'controller',
        'store_request',
        'update_request',
        'title_field',
        'main_columns',
        'translation_columns',
        'casts',
        'scopes',
        'accessors',
        'mutators',
        'translation_mutators',
        'translation_rules',
        'main_rules',
        'attributes',
        'with_relations',
        'upload_parameter',
        'main_data_store',
        'main_data_update',
        'translation_data',
        'translation_data_update',
        'image_uploads',
        'image_deletes',
        'table_cover',
        'datatable_columns',
        'datatable_extra_columns',
        'datatable_raw_columns',
        'datatable_view_columns',
        'table_headings',
        'styles',
        'image_cards',
        'base_cards',
        'main_cards',
        'translation_inputs',
        'media_cards',
        'scripts',
        'submit_scripts',
        'show_cover',
        'show_details',
        'show_translations',
        'show_main',
        'show_media',
    ];

    public function __construct(private readonly string $stubsPath) {}

    public function render(string $stub, array $values): string
    {
        $path = $this->stubsPath . DIRECTORY_SEPARATOR . $stub . '.stub';

        if (!is_file($path)) {
            throw new CrudGeneratorException("Stub [{$stub}] not found.");
        }

        return $this->renderString(file_get_contents($path), $values, $stub);
    }

    public function renderString(string $template, array $values, string $name = 'inline'): string
    {
        $unknown = array_diff(array_keys($values), self::PLACEHOLDERS);

        if ($unknown) {
            throw new CrudGeneratorException("Stub [{$name}] received unknown placeholders: " . implode(', ', $unknown));
        }

        $output = preg_replace_callback(
            '/\[%\s*([a-z_]+)\s*%\]/',
            function ($match) use ($values, $name) {
                if (!array_key_exists($match[1], $values)) {
                    throw new CrudGeneratorException("Stub [{$name}] is missing a value for [{$match[1]}].");
                }

                return (string) $values[$match[1]];
            },
            $template
        );

        return str_replace("\r\n", "\n", $output);
    }
}

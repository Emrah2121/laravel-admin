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
        'plural_variable',
        'morph_alias',
        'controller',
        'store_request',
        'update_request',
        'title_field',
        'main_columns',
        'translation_columns',
        'casts',
        'traits',
        'imports',
        'scopes',
        'accessors',
        'mutators',
        'translation_mutators',
        'translation_rules',
        'main_rules',
        'image_rules',
        'attributes',
        'main_data_store',
        'main_data_update',
        'translation_data',
        'image_uploads',
        'image_deletes',
        'datatable_columns',
        'datatable_raw_columns',
        'table_headings',
        'table_cover',
        'styles',
        'image_cards',
        'base_cards',
        'main_cards',
        'translation_inputs',
        'media_cards',
        'scripts',
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

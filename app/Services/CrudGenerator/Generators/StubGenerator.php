<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\Generator;
use App\Services\CrudGenerator\ModuleSpec;
use App\Services\CrudGenerator\StubRenderer;

abstract class StubGenerator implements Generator
{
    public function __construct(protected readonly StubRenderer $renderer) {}

    /**
     * Placeholders shared by every stub of a module.
     */
    protected function names(ModuleSpec $spec): array
    {
        return [
            'class' => $spec->name,
            'translation_class' => $spec->translationModel(),
            'table' => $spec->table(),
            'translation_table' => $spec->translationTable(),
            'foreign_key' => $spec->foreignKey(),
            'key' => $spec->key(),
            'route_parameter' => $spec->routeParameter(),
            'variable' => $spec->variable(),
            'morph_alias' => $spec->morphAlias(),
            'controller' => $spec->controller(),
            'store_request' => $spec->storeRequest(),
            'update_request' => $spec->updateRequest(),
            'title_field' => $spec->titleField()?->name,
        ];
    }

    /**
     * Joins lines and indents each non-empty one by $spaces.
     */
    protected function lines(array $lines, int $spaces): string
    {
        $indent = str_repeat(' ', $spaces);

        return implode("\n", array_map(
            fn ($line) => $line === '' ? '' : $indent . $line,
            array_merge(...array_map(fn ($line) => explode("\n", $line), $lines ?: ['']))
        ));
    }

    protected function phpList(array $items): string
    {
        return "['" . implode("', '", $items) . "']";
    }

    protected function render(string $stub, ModuleSpec $spec, array $values): string
    {
        $template = $this->renderer->render($stub, array_merge($this->names($spec), $values));

        // Empty optional sections leave whitespace-only lines and blank runs behind.
        $template = preg_replace('/^[ \t]+$/m', '', $template);

        return preg_replace("/\n{3,}/", "\n\n", $template);
    }
}

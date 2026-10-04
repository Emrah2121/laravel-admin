<?php

namespace App\Services\CrudGenerator\Generators;

use App\Services\CrudGenerator\FieldSpec;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\Generator;
use App\Services\CrudGenerator\ModuleSpec;

/**
 * Registers a generated module in the existing files, each change wrapped
 * in a "crud-generator:start/end {Module}" region so it is idempotent.
 */
class InjectionGenerator implements Generator
{
    public const LOCALES = ['az', 'en', 'ru'];

    public function operations(ModuleSpec $spec): array
    {
        return array_merge(
            [
                $this->routes($spec),
                $this->sidebar($spec),
                $this->permissions($spec),
                $this->morphMap($spec),
            ],
            $this->uploadTypes($spec),
            $this->lang($spec),
        );
    }

    private function routes(ModuleSpec $spec): FileOperation
    {
        $names = $spec->routePath() === $spec->key() ? '' : "->names('{$spec->key()}')";

        return FileOperation::inject(
            'routes/backend.php',
            $spec->name,
            "Route::resource('/admins', AdminController::class)",
            "    Route::resource('/{$spec->routePath()}', \\App\\Http\\Controllers\\Backend\\{$spec->controller()}::class){$names};",
            before: true,
            indent: '    ',
        );
    }

    private function sidebar(ModuleSpec $spec): FileOperation
    {
        $body = implode("\n", [
            "@can('{$spec->key()} index')",
            '    <div class="menu-item">',
            "        <a href=\"{{ route('backend.{$spec->key()}.index') }}\"",
            "            class=\"menu-link {{ request()->routeIs('backend.{$spec->key()}.*') ? 'active' : '' }}\">",
            '            <span class="menu-icon">',
            "                <i class=\"ki-outline ki-{$spec->icon} fs-2\"></i>",
            '            </span>',
            "            <span class=\"menu-title\">@lang('backend.titles.{$spec->key()}')</span>",
            '        </a>',
            '    </div>',
            '@endcan',
        ]);

        return FileOperation::inject(
            'resources/views/backend/includes/sidebar.blade.php',
            $spec->name,
            "@if (admin()->can('admins index') || admin()->can('roles index') || admin()->can('permissions index'))",
            $this->indent($body, 12),
            FileOperation::STYLE_BLADE,
            before: true,
            indent: '            ',
        );
    }

    private function permissions(ModuleSpec $spec): FileOperation
    {
        return FileOperation::inject(
            'database/seeders/PermissionSeeder.php',
            $spec->name,
            "'logs',",
            "            '{$spec->key()}',",
            indent: '            ',
        );
    }

    private function morphMap(ModuleSpec $spec): FileOperation
    {
        return FileOperation::inject(
            'app/Providers/AppServiceProvider.php',
            $spec->name,
            "'blog' => Blog::class,",
            "            '{$spec->morphAlias()}' => \\App\\Models\\{$spec->name}::class,",
            indent: '            ',
        );
    }

    /**
     * @return FileOperation[]
     */
    private function uploadTypes(ModuleSpec $spec): array
    {
        if (!$spec->imageFields()) {
            return [];
        }

        $lines = array_map(
            fn (FieldSpec $field) => "            '{$spec->imageCollection($field)}' => \$standardImage,",
            $spec->imageFields()
        );

        return [
            FileOperation::inject(
                'app/Services/UploadDocumentService.php',
                $spec->name,
                "'blog_images' => \$standardImage,",
                implode("\n", $lines),
                indent: '            ',
            ),
        ];
    }

    /**
     * Labels default to the developer's text in every locale; translators
     * can change them in the lang files or the translations panel.
     *
     * @return FileOperation[]
     */
    private function lang(ModuleSpec $spec): array
    {
        $operations = [];

        $labels = array_map(
            fn (FieldSpec $field) => "            '{$spec->labelKey($field)}' => " . var_export($field->label, true) . ',',
            $spec->fields
        );

        foreach (self::LOCALES as $locale) {
            $path = "lang/{$locale}/backend.php";

            $operations[] = FileOperation::inject(
                $path,
                $spec->name . ' titles',
                "    'titles' =>\n        array(",
                "            '{$spec->key()}' => " . var_export($spec->label, true) . ',',
                indent: '            ',
            );

            $operations[] = FileOperation::inject(
                $path,
                $spec->name . ' labels',
                "    'labels' =>\n        array(",
                implode("\n", $labels),
                indent: '            ',
            );
        }

        return $operations;
    }

    private function indent(string $text, int $spaces): string
    {
        $indent = str_repeat(' ', $spaces);

        return implode("\n", array_map(fn ($line) => $line === '' ? '' : $indent . $line, explode("\n", $text)));
    }
}

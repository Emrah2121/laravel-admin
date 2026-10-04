<?php

namespace App\Services\CrudGenerator;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Throwable;

final class SpecValidator
{
    public const MAX_FIELDS = 30;

    private const NAME_PATTERN = '/^[A-Z][A-Za-z0-9]{1,49}$/';

    private const FIELD_PATTERN = '/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/';

    // Labels reach lang files, @lang output and the SweetAlert notify() helper unescaped.
    private const LABEL_PATTERN = '/^[\pL\pN][\pL\pN \-_.,()\/]{0,59}$/u';

    private const ICON_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    private const PHP_RESERVED = [
        '__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class',
        'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
        'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'enum', 'eval', 'exit',
        'extends', 'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if',
        'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list',
        'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'readonly', 'require',
        'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var',
        'while', 'xor', 'yield', 'bool', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null',
        'numeric', 'object', 'parent', 'resource', 'self', 'string', 'true', 'void',
    ];

    // Class names already imported by the generated controller, model and requests.
    private const RESERVED_CLASSES = [
        'App', 'Arr', 'Attribute', 'Controller', 'Db', 'Document', 'DocumentTrait', 'Exception', 'FormRequest',
        'Gate', 'HasMany', 'HasOne', 'Language', 'Log', 'Model', 'Request', 'Response', 'Route', 'Rule',
        'SpatieLogsActivity', 'Storage', 'Str', 'Translation', 'TranslationTrait', 'UploadDocumentService',
        'ValidImageMimeTypeRule',
    ];

    private const RESERVED_FIELDS = [
        'id', 'locale', 'created_at', 'updated_at', 'deleted_at', 'translations', 'translation', 'files',
        'first_image', 'cover_image', 'documents', 'pivot', 'exists', 'incrementing', 'timestamps',
        'connection', 'table', 'attributes', 'relations', 'guarded', 'fillable', 'casts', 'slug', 'status',
        'order', 'publish_date', 'images', 'image_remove', '_token', '_method',
    ];

    public function __construct(
        private readonly Writer $writer,
        private readonly Manifest $manifest,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public function validate(ModuleSpec $spec): array
    {
        $errors = $this->validateStructure($spec);

        if ($errors) {
            return $errors;
        }

        $conflicts = $this->conflicts($spec);

        return $conflicts ? ['name' => $conflicts] : [];
    }

    /**
     * @return array<string, string[]>
     */
    public function validateStructure(ModuleSpec $spec): array
    {
        $errors = [];

        if (!preg_match(self::NAME_PATTERN, $spec->name)) {
            $errors['name'][] = 'Module name must be a singular StudlyCase identifier (letters and digits, 2-50 characters).';
        } elseif ($this->isReservedClass($spec->name)) {
            $errors['name'][] = "Module name [{$spec->name}] is a reserved word.";
        } elseif (str_ends_with($spec->name, 'Translation')) {
            $errors['name'][] = 'Module name must not end with "Translation".';
        }

        if (!preg_match(self::NAME_PATTERN, $spec->plural)) {
            $errors['plural'][] = 'Plural name must be a StudlyCase identifier (letters and digits, 2-50 characters).';
        } elseif ($spec->plural === $spec->name) {
            $errors['plural'][] = 'Plural name must differ from the module name.';
        } elseif ($this->isReservedClass($spec->plural)) {
            $errors['plural'][] = "Plural name [{$spec->plural}] is a reserved word.";
        } elseif ($spec->routeParameter() !== $spec->snake()) {
            $errors['plural'][] = "Plural name must singularize back to the module name ({$spec->routeParameter()} given).";
        }

        if (!preg_match(self::LABEL_PATTERN, $spec->label)) {
            $errors['label'][] = 'Label may only contain letters, digits, spaces and - _ . , ( ) / (max 60 characters).';
        }

        if (!preg_match(self::ICON_PATTERN, $spec->icon) || strlen($spec->icon) > 40) {
            $errors['icon'][] = 'Icon must be a Keen icon name such as "element-11".';
        }

        if (!$spec->fields) {
            $errors['fields'][] = 'Add at least one field.';
        } elseif (count($spec->fields) > self::MAX_FIELDS) {
            $errors['fields'][] = 'A module may have at most ' . self::MAX_FIELDS . ' fields.';
        } elseif (!$spec->titleField()) {
            $errors['fields'][] = 'At least one translatable string field is required (it is used as the title).';
        }

        $reserved = array_merge(self::RESERVED_FIELDS, [$spec->foreignKey()]);
        $names = [];
        $imageAccessors = array_map(fn (FieldSpec $field) => $field->name . '_images', $spec->imageFields());

        foreach ($spec->fields as $index => $field) {
            $key = "fields.{$index}";

            if (!preg_match(self::FIELD_PATTERN, $field->name) || strlen($field->name) > 64) {
                $errors["{$key}.name"][] = 'Field name must be snake_case (lowercase letters, digits and single underscores).';
            } elseif (in_array($field->name, $reserved, true) || in_array($field->name, self::PHP_RESERVED, true)) {
                $errors["{$key}.name"][] = "Field name [{$field->name}] is reserved.";
            } elseif (in_array($field->name, $names, true)) {
                $errors["{$key}.name"][] = "Field name [{$field->name}] is used twice.";
            } elseif (in_array($field->name, $imageAccessors, true)) {
                $errors["{$key}.name"][] = "Field name [{$field->name}] collides with an image accessor.";
            }

            $names[] = $field->name;

            if (!preg_match(self::LABEL_PATTERN, $field->label)) {
                $errors["{$key}.label"][] = 'Label may only contain letters, digits, spaces and - _ . , ( ) / (max 60 characters).';
            }

            if (!in_array($field->type, FieldSpec::TYPES, true)) {
                $errors["{$key}.type"][] = 'Field type must be one of: ' . implode(', ', FieldSpec::TYPES) . '.';
            }

            if ($field->isString() && ($field->maxLength < 1 || $field->maxLength > 255)) {
                $errors["{$key}.max_length"][] = 'Max length must be between 1 and 255.';
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    public function conflicts(ModuleSpec $spec): array
    {
        $conflicts = [];

        $files = [
            "app/Models/{$spec->name}.php",
            "app/Models/{$spec->translationModel()}.php",
            "app/Http/Controllers/Backend/{$spec->controller()}.php",
            "app/Http/Requests/Backend/{$spec->storeRequest()}.php",
            "app/Http/Requests/Backend/{$spec->updateRequest()}.php",
            "resources/views/backend/{$spec->key()}",
        ];

        foreach ($files as $file) {
            if (file_exists($this->writer->path($file))) {
                $conflicts[] = "[{$file}] already exists.";
            }
        }

        foreach ([$spec->table(), $spec->translationTable()] as $table) {
            if (glob($this->writer->path('database/migrations') . DIRECTORY_SEPARATOR . "*_create_{$table}_table.php")) {
                $conflicts[] = "A migration creating table [{$table}] already exists.";
            }

            if ($this->safely(fn () => Schema::hasTable($table))) {
                $conflicts[] = "Database table [{$table}] already exists.";
            }
        }

        if (Route::has("backend.{$spec->key()}.index")) {
            $conflicts[] = "Route [backend.{$spec->key()}.index] already exists.";
        }

        if ($this->uriTaken($spec->routePath())) {
            $conflicts[] = "URL path [{$spec->routePath()}] is already used by another admin route.";
        }

        if ($this->safely(fn () => Permission::where('name', 'like', $spec->key() . ' %')->exists())) {
            $conflicts[] = "Permissions for [{$spec->key()}] already exist.";
        }

        $textChecks = [
            'database/seeders/PermissionSeeder.php' => "'{$spec->key()}',",
            'resources/views/backend/includes/sidebar.blade.php' => "backend.{$spec->key()}.",
            'app/Services/UploadDocumentService.php' => "'{$spec->snake()}_",
            'app/Providers/AppServiceProvider.php' => "'{$spec->morphAlias()}' =>",
        ];

        foreach ($textChecks as $file => $needle) {
            if (str_contains($this->writer->read($file) ?? '', $needle)) {
                $conflicts[] = "[{$file}] already references [{$spec->key()}].";
            }
        }

        if (Relation::getMorphedModel($spec->morphAlias())) {
            $conflicts[] = "Morph alias [{$spec->morphAlias()}] is already registered.";
        }

        if (trans()->has("backend.titles.{$spec->key()}", null, false)) {
            $conflicts[] = "Translation key [backend.titles.{$spec->key()}] already exists.";
        }

        foreach ($spec->fields as $field) {
            if (trans()->has("backend.labels.{$spec->labelKey($field)}", null, false)) {
                $conflicts[] = "Translation key [backend.labels.{$spec->labelKey($field)}] already exists.";
            }
        }

        if ($this->manifest->has($spec->name)) {
            $conflicts[] = "Module [{$spec->name}] was already generated.";
        }

        return $conflicts;
    }

    private function isReservedClass(string $name): bool
    {
        return in_array(strtolower($name), self::PHP_RESERVED, true)
            || in_array(strtolower($name), array_map('strtolower', self::RESERVED_CLASSES), true);
    }

    private function uriTaken(string $path): bool
    {
        $dashboard = Route::getRoutes()->getByName('backend.dashboard');
        $prefix = $dashboard ? trim($dashboard->uri(), '/') : 'gload';
        $uri = trim($prefix . '/' . $path, '/');

        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri || str_starts_with($route->uri(), $uri . '/')) {
                return true;
            }
        }

        return false;
    }

    private function safely(callable $check): bool
    {
        try {
            return (bool) $check();
        } catch (Throwable) {
            return false;
        }
    }
}

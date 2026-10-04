<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Str;

final class ModuleSpec
{
    public const DEFAULT_ICON = 'element-11';

    /**
     * @param  FieldSpec[]  $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly string $plural,
        public readonly string $label,
        public readonly string $icon,
        public readonly bool $hasSlug,
        public readonly bool $hasPublishDate,
        public readonly bool $hasOrder,
        public readonly bool $hasStatus,
        public readonly array $fields,
    ) {}

    public static function fromArray(array $spec): self
    {
        $name = Str::studly(trim((string) ($spec['name'] ?? '')));
        $plural = Str::studly(trim((string) ($spec['plural'] ?? ''))) ?: Str::plural($name);

        return new self(
            name: $name,
            plural: $plural,
            label: trim((string) ($spec['label'] ?? '')) ?: Str::headline($plural),
            icon: trim((string) ($spec['icon'] ?? '')) ?: self::DEFAULT_ICON,
            hasSlug: FieldSpec::bool($spec['slug'] ?? true),
            hasPublishDate: FieldSpec::bool($spec['publish_date'] ?? true),
            hasOrder: FieldSpec::bool($spec['order'] ?? true),
            hasStatus: FieldSpec::bool($spec['status'] ?? true),
            fields: array_values(array_map(
                fn ($field) => FieldSpec::fromArray((array) $field),
                (array) ($spec['fields'] ?? [])
            )),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'plural' => $this->plural,
            'label' => $this->label,
            'icon' => $this->icon,
            'slug' => $this->hasSlug,
            'publish_date' => $this->hasPublishDate,
            'order' => $this->hasOrder,
            'status' => $this->hasStatus,
            'fields' => array_map(fn (FieldSpec $field) => $field->toArray(), $this->fields),
        ];
    }

    /**
     * Snake plural, shared by the table, permissions, route names and view folder.
     */
    public function key(): string
    {
        return Str::snake($this->plural);
    }

    public function table(): string
    {
        return $this->key();
    }

    public function snake(): string
    {
        return Str::snake($this->name);
    }

    public function translationModel(): string
    {
        return $this->name . 'Translation';
    }

    public function translationTable(): string
    {
        return $this->snake() . '_translations';
    }

    public function foreignKey(): string
    {
        return $this->snake() . '_id';
    }

    public function routePath(): string
    {
        return Str::kebab($this->plural);
    }

    /**
     * Mirrors ResourceRegistrar::getResourceWildcard() for the route path.
     */
    public function routeParameter(): string
    {
        return str_replace('-', '_', Str::singular($this->routePath()));
    }

    public function variable(): string
    {
        return Str::camel($this->name);
    }

    public function pluralVariable(): string
    {
        return Str::camel($this->plural);
    }

    public function morphAlias(): string
    {
        return $this->snake();
    }

    public function controller(): string
    {
        return $this->name . 'Controller';
    }

    public function storeRequest(): string
    {
        return 'Store' . $this->name . 'Request';
    }

    public function updateRequest(): string
    {
        return 'Update' . $this->name . 'Request';
    }

    public function permission(string $action): string
    {
        return $this->key() . ' ' . $action;
    }

    public function imageCollection(FieldSpec $field): string
    {
        return $this->snake() . '_' . $field->name;
    }

    public function labelKey(FieldSpec $field): string
    {
        return $this->key() . '_' . $field->name;
    }

    /**
     * @return FieldSpec[]
     */
    public function translatableFields(): array
    {
        return array_values(array_filter($this->fields, fn (FieldSpec $field) => $field->translatable));
    }

    /**
     * @return FieldSpec[]
     */
    public function mainFields(): array
    {
        return array_values(array_filter($this->fields, fn (FieldSpec $field) => !$field->translatable && !$field->isImage()));
    }

    /**
     * @return FieldSpec[]
     */
    public function imageFields(): array
    {
        return array_values(array_filter($this->fields, fn (FieldSpec $field) => $field->isImage()));
    }

    /**
     * @return FieldSpec[]
     */
    public function quillFields(): array
    {
        return array_values(array_filter($this->fields, fn (FieldSpec $field) => $field->quill));
    }

    /**
     * The first translatable string field plays Blog's "title" role.
     */
    public function titleField(): ?FieldSpec
    {
        foreach ($this->translatableFields() as $field) {
            if ($field->isString()) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function baseColumns(): array
    {
        return array_keys(array_filter([
            'slug' => $this->hasSlug,
            'publish_date' => $this->hasPublishDate,
            'order' => $this->hasOrder,
            'status' => $this->hasStatus,
        ]));
    }
}

<?php

namespace App\Services\CrudGenerator;

use Illuminate\Support\Str;

final class FieldSpec
{
    public const TYPE_STRING = 'string';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_IMAGE = 'image';

    public const TYPES = [self::TYPE_STRING, self::TYPE_TEXTAREA, self::TYPE_IMAGE];

    public const DEFAULT_MAX_LENGTH = 255;

    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $required,
        public readonly bool $translatable,
        public readonly ?int $maxLength = null,
        public readonly bool $quill = false,
    ) {}

    public static function fromArray(array $field): self
    {
        $type = (string) ($field['type'] ?? self::TYPE_STRING);
        $name = trim((string) ($field['name'] ?? ''));
        $maxLength = $field['max_length'] ?? null;

        return new self(
            name: $name,
            label: trim((string) ($field['label'] ?? '')) ?: Str::headline($name),
            type: $type,
            required: self::bool($field['required'] ?? false),
            translatable: $type !== self::TYPE_IMAGE && self::bool($field['translatable'] ?? false),
            maxLength: $type === self::TYPE_STRING
                ? (is_numeric($maxLength) ? (int) $maxLength : self::DEFAULT_MAX_LENGTH)
                : null,
            quill: $type === self::TYPE_TEXTAREA && self::bool($field['quill'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'translatable' => $this->translatable,
            'max_length' => $this->maxLength,
            'quill' => $this->quill,
        ];
    }

    public function isString(): bool
    {
        return $this->type === self::TYPE_STRING;
    }

    public function isTextarea(): bool
    {
        return $this->type === self::TYPE_TEXTAREA;
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public static function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

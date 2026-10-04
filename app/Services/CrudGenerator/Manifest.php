<?php

namespace App\Services\CrudGenerator;

final class Manifest
{
    public function __construct(private readonly string $absolutePath) {}

    /**
     * @return array<int, array>
     */
    public function all(): array
    {
        if (!is_file($this->absolutePath)) {
            return [];
        }

        $modules = json_decode(file_get_contents($this->absolutePath), true);

        return is_array($modules['modules'] ?? null) ? $modules['modules'] : [];
    }

    public function find(string $name): ?array
    {
        foreach ($this->all() as $module) {
            if (strcasecmp($module['name'] ?? '', $name) === 0) {
                return $module;
            }
        }

        return null;
    }

    public function has(string $name): bool
    {
        return $this->find($name) !== null;
    }

    public function with(array $entry): string
    {
        $modules = $this->all();
        $modules[] = $entry;

        return json_encode(['modules' => $modules], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

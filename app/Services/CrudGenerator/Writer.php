<?php

namespace App\Services\CrudGenerator;

use Throwable;

final class Writer
{
    private string $basePath;

    private array $createdFiles = [];

    private array $createdDirectories = [];

    private array $originals = [];

    public function __construct(string $basePath)
    {
        $real = realpath($basePath);

        if ($real === false || !is_dir($real)) {
            throw new CrudGeneratorException("Base path [{$basePath}] does not exist.");
        }

        $this->basePath = $real;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * Resolves a project-relative path and guarantees it stays inside the base path.
     */
    public function path(string $relative): string
    {
        if (
            $relative === ''
            || !preg_match('/^[A-Za-z0-9_\-.\/]+$/', $relative)
            || str_starts_with($relative, '/')
            || in_array('..', explode('/', $relative), true)
            || in_array('.', explode('/', $relative), true)
        ) {
            throw new CrudGeneratorException("Unsafe target path [{$relative}].");
        }

        $absolute = $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        $existing = $absolute;

        while (!file_exists($existing)) {
            $existing = dirname($existing);
        }

        $real = realpath($existing);

        if ($real !== $this->basePath && !str_starts_with($real, $this->basePath . DIRECTORY_SEPARATOR)) {
            throw new CrudGeneratorException("Target path [{$relative}] escapes the base path.");
        }

        return $absolute;
    }

    public function read(string $relative): ?string
    {
        $path = $this->path($relative);

        return is_file($path) ? file_get_contents($path) : null;
    }

    /**
     * @param  FileOperation[]  $operations
     * @return string[]
     */
    public function conflicts(array $operations): array
    {
        $conflicts = [];
        $targets = [];

        foreach ($operations as $operation) {
            try {
                $path = $this->path($operation->path);
            } catch (CrudGeneratorException $e) {
                $conflicts[] = $e->getMessage();

                continue;
            }

            if ($operation->type !== FileOperation::INJECT && in_array($operation->path, $targets, true)) {
                $conflicts[] = "Path [{$operation->path}] is targeted twice.";
            }

            $targets[] = $operation->path;

            if ($operation->type === FileOperation::CREATE && file_exists($path)) {
                $conflicts[] = "File [{$operation->path}] already exists.";
            }

            if ($operation->type === FileOperation::INJECT) {
                $current = is_file($path) ? file_get_contents($path) : null;

                if ($current === null) {
                    $conflicts[] = "File [{$operation->path}] to modify does not exist.";
                } elseif (!$operation->hasRegion($current) && $operation->anchorCount($current) !== 1) {
                    $conflicts[] = "Injection point in [{$operation->path}] was not found exactly once.";
                }
            }
        }

        return $conflicts;
    }

    /**
     * Applies injections in memory, in order, without writing anything.
     *
     * @param  FileOperation[]  $operations
     * @return array<int, array{operation: FileOperation, diff: array, changed: bool}>
     */
    public function preview(array $operations): array
    {
        $buffers = [];
        $preview = [];

        foreach ($operations as $operation) {
            if ($operation->type !== FileOperation::INJECT) {
                $preview[] = ['operation' => $operation, 'diff' => [], 'changed' => true];

                continue;
            }

            $current = $buffers[$operation->path] ?? $this->read($operation->path) ?? '';
            $updated = $operation->apply($current);
            $buffers[$operation->path] = $updated;

            $preview[] = [
                'operation' => $operation,
                'diff' => $current === $updated ? [] : FileOperation::diff($current, $updated),
                'changed' => $current !== $updated,
            ];
        }

        return $preview;
    }

    /**
     * Writes every operation or nothing. $after runs inside the same
     * transaction (formatting, linting) and may throw to trigger rollback.
     *
     * @param  FileOperation[]  $operations
     * @return array{created: string[], modified: string[]}
     */
    public function write(array $operations, ?callable $after = null): array
    {
        $conflicts = $this->conflicts($operations);

        if ($conflicts) {
            throw CrudGeneratorException::withErrors(['files' => $conflicts]);
        }

        $this->createdFiles = [];
        $this->createdDirectories = [];
        $this->originals = [];

        $result = ['created' => [], 'modified' => []];

        try {
            foreach ($operations as $operation) {
                $path = $this->path($operation->path);
                $current = is_file($path) ? file_get_contents($path) : null;
                $updated = $operation->apply($current);

                if ($current === $updated) {
                    continue;
                }

                if ($current === null) {
                    $this->makeDirectory(dirname($path));
                    $this->createdFiles[] = $path;
                    $result['created'][] = $operation->path;
                } elseif (!array_key_exists($path, $this->originals)) {
                    $this->originals[$path] = $current;
                    $result['modified'][] = $operation->path;
                }

                if (file_put_contents($path, $updated, LOCK_EX) === false) {
                    throw new CrudGeneratorException("Could not write [{$operation->path}].");
                }
            }

            if ($after) {
                $after($result);
            }
        } catch (Throwable $e) {
            $this->rollback();

            throw $e;
        }

        return $result;
    }

    private function rollback(): void
    {
        foreach ($this->originals as $path => $contents) {
            file_put_contents($path, $contents, LOCK_EX);
        }

        foreach (array_reverse($this->createdFiles) as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        foreach (array_reverse($this->createdDirectories) as $directory) {
            if (is_dir($directory) && count(scandir($directory)) === 2) {
                rmdir($directory);
            }
        }
    }

    private function makeDirectory(string $directory): void
    {
        $missing = [];

        while (!is_dir($directory)) {
            $missing[] = $directory;
            $directory = dirname($directory);
        }

        foreach (array_reverse($missing) as $path) {
            if (!mkdir($path) && !is_dir($path)) {
                throw new CrudGeneratorException("Could not create directory [{$path}].");
            }

            $this->createdDirectories[] = $path;
        }
    }
}

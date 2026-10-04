<?php

namespace App\Services\CrudGenerator;

use App\Services\CrudGenerator\Generators\ControllerGenerator;
use App\Services\CrudGenerator\Generators\InjectionGenerator;
use App\Services\CrudGenerator\Generators\MigrationGenerator;
use App\Services\CrudGenerator\Generators\ModelGenerator;
use App\Services\CrudGenerator\Generators\RequestGenerator;
use App\Services\CrudGenerator\Generators\ViewGenerator;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\PhpExecutableFinder;

final class CrudGenerator
{
    /**
     * @param  Generator[]  $generators
     */
    public function __construct(
        private readonly Writer $writer,
        private readonly SpecValidator $validator,
        private readonly Manifest $manifest,
        private readonly array $generators,
        private readonly string $manifestPath,
        private readonly string $pintConfig,
    ) {}

    public static function make(?string $basePath = null): self
    {
        $writer = new Writer($basePath ?? config('crud-generator.base_path'));
        $manifestPath = config('crud-generator.manifest');
        $manifest = new Manifest($writer->path($manifestPath));
        $stubsPath = config('crud-generator.stubs_path');
        $renderer = new StubRenderer($stubsPath);

        return new self(
            $writer,
            new SpecValidator($writer, $manifest),
            $manifest,
            self::generators($renderer),
            $manifestPath,
            $stubsPath . DIRECTORY_SEPARATOR . 'pint.json',
        );
    }

    /**
     * @return Generator[]
     */
    private static function generators(StubRenderer $renderer): array
    {
        return [
            new MigrationGenerator($renderer),
            new ModelGenerator($renderer),
            new RequestGenerator($renderer),
            new ControllerGenerator($renderer),
            new ViewGenerator($renderer),
            new InjectionGenerator(),
        ];
    }

    public function writer(): Writer
    {
        return $this->writer;
    }

    public function manifest(): Manifest
    {
        return $this->manifest;
    }

    /**
     * @return array<string, string[]>
     */
    public function validate(ModuleSpec $spec): array
    {
        return $this->validator->validate($spec);
    }

    /**
     * @return FileOperation[]
     */
    public function operations(ModuleSpec $spec): array
    {
        $operations = [];

        foreach ($this->generators as $generator) {
            array_push($operations, ...$generator->operations($spec));
        }

        return $operations;
    }

    /**
     * Dry run: validation errors, file conflicts and a preview of every change.
     */
    public function plan(ModuleSpec $spec): array
    {
        $errors = $this->validate($spec);

        if ($errors) {
            return ['errors' => $errors, 'conflicts' => [], 'preview' => []];
        }

        $operations = $this->operations($spec);
        $conflicts = $this->writer->conflicts($operations);

        return [
            'errors' => [],
            'conflicts' => $conflicts,
            'preview' => $conflicts ? [] : $this->writer->preview($operations),
        ];
    }

    /**
     * @return array{created: string[], modified: string[], artisan: array<string, array{successful: bool, output: string}>}
     */
    public function generate(ModuleSpec $spec, bool $migrate = false, bool $seed = false): array
    {
        if (($migrate || $seed) && !$this->artisanAllowed()) {
            throw new CrudGeneratorException('Running migrations or seeders is not allowed in this environment.');
        }

        $errors = $this->validate($spec);

        if ($errors) {
            throw CrudGeneratorException::withErrors($errors);
        }

        $operations = $this->operations($spec);
        $operations[] = FileOperation::put($this->manifestPath, $this->manifest->with([
            'name' => $spec->name,
            'key' => $spec->key(),
            'generated_at' => now()->toIso8601String(),
            'spec' => $spec->toArray(),
            'files' => array_values(array_map(
                fn (FileOperation $operation) => $operation->path,
                array_filter($operations, fn (FileOperation $operation) => $operation->type === FileOperation::CREATE)
            )),
            'regions' => array_values(array_map(
                fn (FileOperation $operation) => ['path' => $operation->path, 'marker' => $operation->startMarker()],
                array_filter($operations, fn (FileOperation $operation) => $operation->type === FileOperation::INJECT)
            )),
        ]));

        $result = $this->writer->write($operations, function (array $result) {
            $this->format($result['created']);
            $this->lint(array_merge($result['created'], $result['modified']));
        });

        // Files are written at this point; Artisan failures are reported, not rolled back.
        $result['artisan'] = [];

        if (app()->routesAreCached()) {
            $result['artisan']['route:clear'] = $this->artisan(['route:clear']);
        }

        if ($migrate) {
            $migrations = array_values(array_filter($result['created'], fn ($path) => str_starts_with($path, 'database/migrations/')));
            $result['artisan']['migrate'] = $this->artisan(array_merge(
                ['migrate', '--force', '--realpath'],
                array_map(fn ($path) => '--path=' . $this->writer->path($path), $migrations)
            ));
        }

        if ($seed) {
            $result['artisan']['seed'] = $this->artisan(['db:seed', '--class=Database\\Seeders\\PermissionSeeder', '--force']);
        }

        return $result;
    }

    public function artisanAllowed(): bool
    {
        return app()->environment(config('crud-generator.artisan_environments', []));
    }

    /**
     * @param  string[]  $paths
     */
    private function format(array $paths): void
    {
        $files = $this->phpFiles($paths);

        if (!$files) {
            return;
        }

        $process = Process::path(base_path())->run(array_merge(
            [$this->php(), base_path('vendor/laravel/pint/builds/pint'), '--config=' . $this->pintConfig],
            $files
        ));

        if (!$process->successful()) {
            throw new CrudGeneratorException('Pint failed: ' . trim($process->output() . $process->errorOutput()));
        }
    }

    /**
     * @param  string[]  $paths
     */
    private function lint(array $paths): void
    {
        foreach ($this->phpFiles($paths) as $file) {
            $process = Process::run([$this->php(), '-l', $file]);

            if (!$process->successful()) {
                throw new CrudGeneratorException('Syntax error in generated file: ' . trim($process->output() . $process->errorOutput()));
            }
        }
    }

    /**
     * @return array{successful: bool, output: string}
     */
    private function artisan(array $arguments): array
    {
        $process = Process::path(base_path())->timeout(300)->run(array_merge([$this->php(), 'artisan'], $arguments));

        return [
            'successful' => $process->successful(),
            'output' => trim($process->output() . "\n" . $process->errorOutput()),
        ];
    }

    /**
     * @return string[]
     */
    private function phpFiles(array $paths): array
    {
        $files = array_filter($paths, fn ($path) => str_ends_with($path, '.php') && !str_ends_with($path, '.blade.php'));

        return array_values(array_map(fn ($path) => $this->writer->path($path), $files));
    }

    private function php(): string
    {
        return (new PhpExecutableFinder())->find(false) ?: 'php';
    }
}

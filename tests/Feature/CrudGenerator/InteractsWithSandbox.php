<?php

namespace Tests\Feature\CrudGenerator;

use App\Services\CrudGenerator\ModuleSpec;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Runs the generator against a temporary copy of the files it reads and
 * modifies, so tests never touch the real codebase.
 */
trait InteractsWithSandbox
{
    protected string $sandbox;

    protected const SANDBOX_DIRECTORIES = [
        'app/Models',
        'app/Http/Controllers/Backend',
        'app/Http/Requests/Backend',
        'database/migrations',
        'resources/views/backend/blogs',
    ];

    protected const SANDBOX_FILES = [
        'routes/backend.php',
        'resources/views/backend/includes/sidebar.blade.php',
        'database/seeders/PermissionSeeder.php',
        'app/Providers/AppServiceProvider.php',
        'app/Services/UploadDocumentService.php',
        'lang/az/backend.php',
        'lang/en/backend.php',
        'lang/ru/backend.php',
    ];

    protected function setUpSandbox(): void
    {
        $this->sandbox = storage_path('framework/testing/crud-sandbox-' . Str::random(8));

        foreach (self::SANDBOX_DIRECTORIES as $directory) {
            File::copyDirectory(base_path($directory), $this->sandbox . '/' . $directory);
        }

        foreach (self::SANDBOX_FILES as $file) {
            File::ensureDirectoryExists(dirname($this->sandbox . '/' . $file));
            File::copy(base_path($file), $this->sandbox . '/' . $file);
        }

        config([
            'crud-generator.enabled' => true,
            'crud-generator.base_path' => $this->sandbox,
        ]);

        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($this->sandbox));
    }

    protected function sandboxPath(string $relative = ''): string
    {
        return $this->sandbox . ($relative === '' ? '' : '/' . $relative);
    }

    /**
     * @return array<string, string> relative path => sha1
     */
    protected function sandboxSnapshot(): array
    {
        $snapshot = [];

        foreach (File::allFiles($this->sandbox) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $snapshot[$relative] = sha1_file($file->getRealPath());
        }

        ksort($snapshot);

        return $snapshot;
    }

    protected function sampleSpec(array $overrides = []): ModuleSpec
    {
        return ModuleSpec::fromArray(array_merge([
            'name' => 'Gadget',
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'string', 'required' => true, 'translatable' => true, 'max_length' => 200],
                ['name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => true, 'translatable' => true, 'quill' => true],
                ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => false, 'translatable' => true, 'quill' => false],
                ['name' => 'sku', 'label' => 'SKU', 'type' => 'string', 'required' => true, 'translatable' => false],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'required' => false, 'translatable' => false, 'quill' => true],
                ['name' => 'cover', 'label' => 'Cover', 'type' => 'image', 'required' => false],
            ],
        ], $overrides));
    }
}

<?php

namespace Tests\Feature\CrudGenerator;

use App\Services\CrudGenerator\CrudGenerator;
use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\CrudGenerator\ModuleSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class GeneratorOutputTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    private const SNAPSHOTS = 'tests/Fixtures/crud-generator';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();
        Carbon::setTestNow('2030-01-02 03:04:05');
    }

    public function test_generated_files_match_snapshots(): void
    {
        $result = CrudGenerator::make()->generate($this->sampleSpec());

        $created = array_values(array_filter($result['created'], fn ($path) => !str_ends_with($path, 'manifest.json')));

        $this->assertCount(12, $created);

        foreach ($created as $path) {
            $snapshot = base_path(self::SNAPSHOTS . '/gadget/' . $path . '.snap');
            $actual = file_get_contents($this->sandboxPath($path));

            // Run with UPDATE_SNAPSHOTS=1 after an intended stub change.
            if (env('UPDATE_SNAPSHOTS') || !is_file($snapshot)) {
                File::ensureDirectoryExists(dirname($snapshot));
                file_put_contents($snapshot, $actual);
            }

            $this->assertSame(file_get_contents($snapshot), $actual, "Snapshot mismatch for {$path}");
        }
    }

    public function test_generated_php_and_blade_are_valid_and_pint_clean(): void
    {
        foreach ([$this->sampleSpec(), $this->minimalSpec()] as $spec) {
            $result = CrudGenerator::make()->generate($spec);
            $this->assertGeneratedFilesAreValid($result['created']);
        }
    }

    public function test_minimal_spec_drops_unused_imports_and_sections(): void
    {
        CrudGenerator::make()->generate($this->minimalSpec());

        $controller = file_get_contents($this->sandboxPath('app/Http/Controllers/Backend/NoteController.php'));
        $request = file_get_contents($this->sandboxPath('app/Http/Requests/Backend/StoreNoteRequest.php'));
        $model = file_get_contents($this->sandboxPath('app/Models/Note.php'));
        $form = file_get_contents($this->sandboxPath('resources/views/backend/notes/form.blade.php'));

        $this->assertStringNotContainsString('UploadDocumentService', $controller);
        $this->assertStringNotContainsString('Storage', $controller);
        $this->assertStringNotContainsString('\'status\' => $data', $controller);
        $this->assertStringNotContainsString('badge(', $controller);
        $this->assertStringNotContainsString('ValidImageMimeTypeRule', $request);
        $this->assertStringNotContainsString('Rule;', $request);
        $this->assertStringNotContainsString('scopeActive', $model);
        $this->assertStringNotContainsString('$casts', $model);
        $this->assertStringNotContainsString('Quill', $form);
        $this->assertStringNotContainsString('flatpickr', $form);
        $this->assertStringNotContainsString('backend.includes.media', $form);
    }

    public function test_quill_is_initialized_only_for_quill_fields(): void
    {
        CrudGenerator::make()->generate($this->sampleSpec());

        $form = file_get_contents($this->sandboxPath('resources/views/backend/gadgets/form.blade.php'));

        $this->assertStringContainsString("new Quill('#kt_gadget_body_{{ \$lang->code }}'", $form);
        $this->assertStringContainsString("new Quill('#kt_gadget_notes'", $form);
        $this->assertStringNotContainsString('kt_gadget_summary', $form);
        $this->assertStringContainsString('<textarea name="summary:{{ $lang->code }}" rows="5"', $form);
    }

    public function test_manifest_records_the_module(): void
    {
        CrudGenerator::make()->generate($this->sampleSpec());

        $module = CrudGenerator::make()->manifest()->find('Gadget');

        $this->assertSame('gadgets', $module['key']);
        $this->assertSame($this->sampleSpec()->toArray(), $module['spec']);
        $this->assertContains('app/Models/Gadget.php', $module['files']);
    }

    public function test_second_generation_of_the_same_module_is_rejected(): void
    {
        CrudGenerator::make()->generate($this->sampleSpec());

        $errors = CrudGenerator::make()->validate($this->sampleSpec());

        $this->assertArrayHasKey('name', $errors);
    }

    public function test_child_processes_get_a_writable_temp_directory(): void
    {
        // "php artisan serve" strips TEMP/TMP; without them the Pint phar cannot extract itself.
        Process::fake();

        CrudGenerator::make()->generate($this->sampleSpec());

        $temp = storage_path('framework/cache/crud-generator');

        Process::assertRan(fn ($process) => str_contains(implode(' ', (array) $process->command), 'pint')
            && ($process->environment['TEMP'] ?? null) === $temp
            && ($process->environment['TMP'] ?? null) === $temp);
        Process::assertRan(fn ($process) => in_array('-l', (array) $process->command, true)
            && ($process->environment['TEMP'] ?? null) === $temp);
        $this->assertDirectoryExists($temp);
    }

    public function test_concurrent_generation_is_refused(): void
    {
        $lock = Cache::lock('crud-generator', 10);
        $lock->get();

        try {
            CrudGenerator::make()->generate($this->sampleSpec());
            $this->fail('Expected the lock to block generation.');
        } catch (CrudGeneratorException $e) {
            $this->assertStringContainsString('Another module', $e->getMessage());
        } finally {
            $lock->release();
        }

        $this->assertFileDoesNotExist($this->sandboxPath('app/Models/Gadget.php'));
    }

    private function minimalSpec(): ModuleSpec
    {
        return ModuleSpec::fromArray([
            'name' => 'Note',
            'slug' => false,
            'publish_date' => false,
            'order' => false,
            'status' => false,
            'fields' => [
                ['name' => 'title', 'label' => 'Title', 'type' => 'string', 'required' => true, 'translatable' => true],
            ],
        ]);
    }

    private function assertGeneratedFilesAreValid(array $paths): void
    {
        foreach ($paths as $path) {
            $absolute = $this->sandboxPath($path);

            if (str_ends_with($path, '.blade.php')) {
                $compiled = $this->sandboxPath('compiled-' . md5($path) . '.php');
                file_put_contents($compiled, Blade::compileString(file_get_contents($absolute)));
                $absolute = $compiled;
            } elseif (!str_ends_with($path, '.php')) {
                continue;
            }

            $lint = Process::run([PHP_BINARY, '-l', $absolute]);
            $this->assertTrue($lint->successful(), "php -l failed for {$path}: " . $lint->output());
        }

        $php = array_values(array_filter($paths, fn ($path) => str_ends_with($path, '.php') && !str_ends_with($path, '.blade.php')));
        $pint = Process::path(base_path())->run(array_merge(
            [PHP_BINARY, base_path('vendor/laravel/pint/builds/pint'), '--test', '--config=' . base_path('stubs/crud-generator/pint.json')],
            array_map(fn ($path) => $this->sandboxPath($path), $php)
        ));

        $this->assertTrue($pint->successful(), 'Pint --test failed: ' . $pint->output());
    }
}

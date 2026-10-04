<?php

namespace Tests\Feature\CrudGenerator;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudMakeCommandTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    private string $specFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();
        $this->specFile = $this->sandboxPath('spec.json');
        file_put_contents($this->specFile, json_encode($this->sampleSpec()->toArray()));
    }

    public function test_dry_run_lists_files_without_writing(): void
    {
        $before = $this->sandboxSnapshot();

        $this->artisan('crud:make', ['--spec' => $this->specFile, '--dry-run' => true])
            ->expectsOutputToContain('app/Models/Gadget.php')
            ->expectsOutputToContain('Dry run: nothing was written.')
            ->assertSuccessful();

        $this->assertSame($before, $this->sandboxSnapshot());
    }

    public function test_generates_the_module(): void
    {
        $this->artisan('crud:make', ['--spec' => $this->specFile])
            ->expectsOutputToContain('Module [Gadget] generated.')
            ->assertSuccessful();

        $this->assertFileExists($this->sandboxPath('app/Http/Controllers/Backend/GadgetController.php'));
    }

    public function test_invalid_spec_is_rejected(): void
    {
        file_put_contents($this->specFile, json_encode(['name' => 'Class', 'fields' => []]));

        $this->artisan('crud:make', ['--spec' => $this->specFile])->assertFailed();
    }

    public function test_refuses_to_run_when_disabled(): void
    {
        config(['crud-generator.enabled' => false]);

        $this->artisan('crud:make', ['--spec' => $this->specFile])
            ->expectsOutputToContain('disabled')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->sandboxPath('app/Models/Gadget.php'));
    }

    public function test_migrate_option_is_refused_outside_allowed_environments(): void
    {
        $before = $this->sandboxSnapshot();

        $this->artisan('crud:make', ['--spec' => $this->specFile, '--migrate' => true])->assertFailed();

        $this->assertSame($before, $this->sandboxSnapshot());
    }
}

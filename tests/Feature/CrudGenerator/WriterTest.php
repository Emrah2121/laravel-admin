<?php

namespace Tests\Feature\CrudGenerator;

use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class WriterTest extends TestCase
{
    use InteractsWithSandbox;

    private Writer $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();
        $this->writer = new Writer($this->sandbox);
    }

    public static function unsafePaths(): array
    {
        return [
            'parent traversal' => ['../outside.php'],
            'nested traversal' => ['app/../../outside.php'],
            'absolute unix' => ['/etc/passwd'],
            'absolute windows' => ['C:/Windows/x.php'],
            'backslash' => ['app\\x.php'],
            'dot segment' => ['app/./x.php'],
            'null byte' => ["app/x.php\0"],
            'empty' => [''],
        ];
    }

    #[DataProvider('unsafePaths')]
    public function test_rejects_paths_outside_the_base_path(string $path): void
    {
        $this->expectException(CrudGeneratorException::class);

        $this->writer->path($path);
    }

    public function test_creates_files_and_directories(): void
    {
        $result = $this->writer->write([FileOperation::create('app/New/Dir/File.php', '<?php')]);

        $this->assertSame(['created' => ['app/New/Dir/File.php'], 'modified' => []], $result);
        $this->assertFileExists($this->sandboxPath('app/New/Dir/File.php'));
    }

    public function test_never_overwrites_existing_files(): void
    {
        $before = $this->sandboxSnapshot();

        try {
            $this->writer->write([
                FileOperation::create('app/Fresh.php', '<?php'),
                FileOperation::create('app/Models/Blog.php', '<?php // overwritten'),
            ]);
            $this->fail('Expected a conflict.');
        } catch (CrudGeneratorException $e) {
            $this->assertStringContainsString('app/Models/Blog.php', $e->getMessage());
        }

        $this->assertSame($before, $this->sandboxSnapshot());
    }

    public function test_missing_injection_anchor_is_reported_as_conflict(): void
    {
        $conflicts = $this->writer->conflicts([
            FileOperation::inject('routes/backend.php', 'Gadget', 'not-in-the-file', 'x'),
            FileOperation::inject('routes/missing.php', 'Gadget', 'x', 'x'),
        ]);

        $this->assertCount(2, $conflicts);
    }

    public function test_rolls_back_everything_when_a_later_step_fails(): void
    {
        $before = $this->sandboxSnapshot();

        try {
            $this->writer->write(
                [
                    FileOperation::create('app/Rollback/Deep/File.php', '<?php'),
                    FileOperation::inject('routes/backend.php', 'Gadget', "Route::resource('/blogs', BlogController::class);", '    // injected'),
                ],
                fn () => throw new RuntimeException('formatter failed')
            );
            $this->fail('Expected the after hook to fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('formatter failed', $e->getMessage());
        }

        $this->assertSame($before, $this->sandboxSnapshot());
        $this->assertDirectoryDoesNotExist($this->sandboxPath('app/Rollback'));
    }

    public function test_preview_does_not_write(): void
    {
        $before = $this->sandboxSnapshot();

        $preview = $this->writer->preview([
            FileOperation::inject('database/seeders/PermissionSeeder.php', 'Gadget', "'logs',", "            'gadgets',"),
        ]);

        $this->assertSame($before, $this->sandboxSnapshot());
        $this->assertTrue($preview[0]['changed']);
        $this->assertContains("            'gadgets',", array_column(array_filter($preview[0]['diff'], fn ($line) => $line['type'] === 'add'), 'text'));
    }

    public function test_reapplying_the_same_injection_changes_nothing(): void
    {
        $operation = FileOperation::inject('database/seeders/PermissionSeeder.php', 'Gadget', "'logs',", "            'gadgets',");

        $this->writer->write([$operation]);
        $once = file_get_contents($this->sandboxPath('database/seeders/PermissionSeeder.php'));
        $result = $this->writer->write([$operation]);

        $this->assertSame([], $result['modified']);
        $this->assertSame($once, file_get_contents($this->sandboxPath('database/seeders/PermissionSeeder.php')));
    }
}

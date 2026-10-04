<?php

namespace Tests\Feature\CrudGenerator;

use App\Services\CrudGenerator\CrudGenerator;
use App\Services\CrudGenerator\Generators\InjectionGenerator;
use App\Services\CrudGenerator\ModuleSpec;
use App\Services\CrudGenerator\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class InjectionTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();
    }

    public function test_generation_registers_the_module_everywhere(): void
    {
        $result = CrudGenerator::make()->generate($this->sampleSpec());

        $this->assertEqualsCanonicalizing([
            'routes/backend.php',
            'resources/views/backend/includes/sidebar.blade.php',
            'database/seeders/PermissionSeeder.php',
            'app/Providers/AppServiceProvider.php',
            'app/Services/UploadDocumentService.php',
            'lang/az/backend.php',
            'lang/en/backend.php',
            'lang/ru/backend.php',
        ], $result['modified']);

        $this->assertStringContainsString(
            "    // crud-generator:start Gadget\n    Route::resource('/gadgets', \\App\\Http\\Controllers\\Backend\\GadgetController::class);\n    // crud-generator:end Gadget\n\n    Route::resource('/admins'",
            $this->sandboxFile('routes/backend.php')
        );
        $this->assertStringContainsString("            'gadgets',\n", $this->sandboxFile('database/seeders/PermissionSeeder.php'));
        $this->assertStringContainsString("'gadget' => \\App\\Models\\Gadget::class,", $this->sandboxFile('app/Providers/AppServiceProvider.php'));
        $this->assertStringContainsString("'gadget_cover' => \$standardImage,", $this->sandboxFile('app/Services/UploadDocumentService.php'));

        $sidebar = $this->sandboxFile('resources/views/backend/includes/sidebar.blade.php');
        $this->assertStringContainsString("@can('gadgets index')", $sidebar);
        $this->assertStringContainsString("request()->routeIs('backend.gadgets.*')", $sidebar);
        $this->assertStringContainsString('ki-outline ki-element-11', $sidebar);

        foreach (InjectionGenerator::LOCALES as $locale) {
            $lang = require $this->sandboxPath("lang/{$locale}/backend.php");

            $this->assertSame('Gadgets', $lang['titles']['gadgets']);
            $this->assertSame('SKU', $lang['labels']['gadgets_sku']);
            $this->assertSame('Cover', $lang['labels']['gadgets_cover']);
        }
    }

    public function test_modified_files_stay_valid(): void
    {
        CrudGenerator::make()->generate($this->sampleSpec());

        $sidebar = $this->sandboxPath('compiled-sidebar.php');
        file_put_contents($sidebar, Blade::compileString($this->sandboxFile('resources/views/backend/includes/sidebar.blade.php')));

        foreach ([
            $sidebar,
            $this->sandboxPath('routes/backend.php'),
            $this->sandboxPath('database/seeders/PermissionSeeder.php'),
            $this->sandboxPath('app/Providers/AppServiceProvider.php'),
            $this->sandboxPath('app/Services/UploadDocumentService.php'),
            $this->sandboxPath('lang/az/backend.php'),
        ] as $file) {
            $this->assertTrue(Process::run([PHP_BINARY, '-l', $file])->successful(), "php -l failed for {$file}");
        }
    }

    public function test_injectors_are_idempotent(): void
    {
        $writer = new Writer($this->sandbox);
        $operations = (new InjectionGenerator())->operations($this->sampleSpec());

        $writer->write($operations);
        $once = $this->sandboxSnapshot();
        $second = $writer->write($operations);

        $this->assertSame([], $second['modified']);
        $this->assertSame($once, $this->sandboxSnapshot());
        $this->assertSame(1, substr_count($this->sandboxFile('routes/backend.php'), "Route::resource('/gadgets'"));
        $this->assertSame(1, substr_count($this->sandboxFile('database/seeders/PermissionSeeder.php'), "'gadgets',"));
        $this->assertSame(1, substr_count($this->sandboxFile('resources/views/backend/includes/sidebar.blade.php'), "@can('gadgets index')"));
        $this->assertSame(1, substr_count($this->sandboxFile('lang/en/backend.php'), "'gadgets_title'"));
    }

    public function test_several_modules_get_separate_regions(): void
    {
        CrudGenerator::make()->generate($this->sampleSpec());
        CrudGenerator::make()->generate(ModuleSpec::fromArray([
            'name' => 'BlogPost',
            'fields' => [['name' => 'title', 'label' => 'Title', 'type' => 'string', 'required' => true, 'translatable' => true]],
        ]));

        $routes = $this->sandboxFile('routes/backend.php');

        $this->assertStringContainsString("Route::resource('/blog-posts', \\App\\Http\\Controllers\\Backend\\BlogPostController::class)->names('blog_posts');", $routes);
        $this->assertStringContainsString('// crud-generator:start Gadget', $routes);
        $this->assertStringContainsString('// crud-generator:start BlogPost', $routes);
        $this->assertStringNotContainsString("'blog_post_", $this->sandboxFile('app/Services/UploadDocumentService.php'));
    }

    public function test_preview_shows_diffs_for_modified_files(): void
    {
        $plan = CrudGenerator::make()->plan($this->sampleSpec());

        $modified = array_values(array_filter($plan['preview'], fn ($item) => $item['operation']->type === 'inject'));
        $paths = array_unique(array_map(fn ($item) => $item['operation']->path, $modified));

        $this->assertCount(8, $paths);

        foreach ($modified as $item) {
            $this->assertNotEmpty(array_filter($item['diff'], fn ($line) => $line['type'] === 'add'));
            $this->assertEmpty(array_filter($item['diff'], fn ($line) => $line['type'] === 'remove'));
        }
    }

    private function sandboxFile(string $path): string
    {
        return file_get_contents($this->sandboxPath($path));
    }
}

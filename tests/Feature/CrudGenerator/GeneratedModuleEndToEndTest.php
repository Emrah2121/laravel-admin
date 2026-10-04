<?php

namespace Tests\Feature\CrudGenerator;

use App\Models\Admin;
use App\Models\Document;
use App\Models\Gadget;
use App\Models\Language;
use App\Services\CrudGenerator\CrudGenerator;
use App\Services\UploadDocumentService;
use Database\Seeders\SandboxPermissionSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Generates a module into the sandbox, loads it into the running app and
 * drives its CRUD routes like a user would.
 */
class GeneratedModuleEndToEndTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    private const LOCALES = ['az', 'en', 'ru'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();

        foreach (self::LOCALES as $code) {
            Language::create(['name' => strtoupper($code), 'code' => $code, 'default' => $code === 'az', 'status' => true]);
        }

        Cache::forget('active_langs');
        Storage::fake('documents');
    }

    public function test_generated_module_works_end_to_end(): void
    {
        $result = CrudGenerator::make()->generate($this->sampleSpec());
        $this->loadGeneratedModule($result['created']);

        $admin = $this->adminWithSeededPermissions();
        $outsider = $this->adminWithRole('Editor', 'editor@example.com');

        $this->actingAs($admin, 'admin')
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertSee(route('backend.gadgets.index'), false);

        $this->actingAs($outsider, 'admin')
            ->get(route('backend.dashboard'))
            ->assertOk()
            ->assertDontSee(route('backend.gadgets.index'), false);

        $this->actingAs($outsider, 'admin')->get(route('backend.gadgets.index'))->assertForbidden();
        // Like Blog, FormRequest::authorize() is true and the Gate check runs in the controller.
        $this->actingAs($outsider, 'admin')->post(route('backend.gadgets.store'), $this->payload('Blocked'))->assertForbidden();

        $this->actingAs($admin, 'admin')->get(route('backend.gadgets.index'))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('backend.gadgets.create'))->assertOk()->assertSee('kt_gadget_body_az', false);

        $this->actingAs($admin, 'admin')
            ->post(route('backend.gadgets.store'), $this->payload('First') + [
                'cover' => [UploadedFile::fake()->image('cover.jpg', 40, 40)],
            ])
            ->assertRedirect(route('backend.gadgets.index'))
            ->assertSessionHas('success');

        $gadget = DB::table('gadgets')->first();
        $this->assertSame('first', $gadget->slug);
        $this->assertSame('SKU-First', $gadget->sku);
        $this->assertStringNotContainsString('<script', $gadget->notes);
        $this->assertStringContainsString('<strong>notes</strong>', $gadget->notes);

        $translations = DB::table('gadget_translations')->where('gadget_id', $gadget->id)->get()->keyBy('locale');
        $this->assertEqualsCanonicalizing(self::LOCALES, $translations->keys()->all());
        $this->assertSame('First en', $translations['en']->title);
        $this->assertStringNotContainsString('<script', $translations['ru']->body);
        $this->assertStringNotContainsString('onerror', $translations['ru']->body);
        $this->assertStringContainsString('<p>Body ru</p>', $translations['ru']->body);
        $this->assertSame('Summary az', $translations['az']->summary);

        $document = Document::where('manipulationable_type', 'gadget')->where('manipulationable_id', $gadget->id)->first();
        $this->assertSame('gadget_cover', $document->collection_name);
        $stored = $document->getAttributes()['document'];
        Storage::disk('documents')->assertExists($stored);

        $this->actingAs($admin, 'admin')
            ->get(route('backend.gadgets.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonFragment(['recordsTotal' => 1]);

        $this->actingAs($admin, 'admin')
            ->get(route('backend.gadgets.edit', ['gadget' => $gadget->id]))
            ->assertOk()
            ->assertSee('First ru')
            ->assertSee('SKU-First');

        $this->actingAs($admin, 'admin')
            ->get(route('backend.gadgets.show', ['gadget' => $gadget->id]))
            ->assertOk()
            ->assertSee('First az');

        $this->actingAs($admin, 'admin')
            ->put(route('backend.gadgets.update', ['gadget' => $gadget->id]), $this->payload('Second'))
            ->assertRedirect(route('backend.gadgets.index'));

        $this->assertSame('Second az', DB::table('gadget_translations')->where('gadget_id', $gadget->id)->where('locale', 'az')->value('title'));
        $this->assertSame(3, DB::table('gadget_translations')->where('gadget_id', $gadget->id)->count());
        $this->assertSame('second', DB::table('gadgets')->value('slug'));

        $this->actingAs($admin, 'admin')
            ->from(route('backend.gadgets.create'))
            ->post(route('backend.gadgets.store'), $this->payload('Second'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin, 'admin')
            ->from(route('backend.gadgets.create'))
            ->post(route('backend.gadgets.store'), Arr::except($this->payload('Third'), ['title:en']))
            ->assertSessionHasErrors('title:en');

        $this->actingAs($admin, 'admin')
            ->delete(route('backend.gadgets.destroy', ['gadget' => $gadget->id]))
            ->assertOk()
            ->assertJson(['status' => 1]);

        $this->assertSame(0, DB::table('gadgets')->count());
        $this->assertSame(0, DB::table('gadget_translations')->count());
        $this->assertSame(0, Document::count());
        Storage::disk('documents')->assertMissing($stored);
    }

    private function payload(string $title): array
    {
        $payload = [
            'slug' => strtolower($title),
            'publish_date' => '2030-01-02',
            'status' => '1',
            'order' => '3',
            'sku' => "SKU-{$title}",
            'notes' => '<p><strong>notes</strong></p><script>alert(1)</script>',
        ];

        foreach (self::LOCALES as $code) {
            $payload["title:{$code}"] = "{$title} {$code}";
            $payload["body:{$code}"] = "<p>Body {$code}</p><img src=\"x\" onerror=\"alert(1)\"><script>alert(1)</script>";
            $payload["summary:{$code}"] = "Summary {$code}";
        }

        return $payload;
    }

    /**
     * Loads the generated classes, views, routes and registrations from the
     * sandbox the same way the real app would after generation.
     */
    private function loadGeneratedModule(array $created): void
    {
        foreach ($created as $path) {
            if (str_starts_with($path, 'app/') && str_ends_with($path, '.php')) {
                require_once $this->sandboxPath($path);
            }
        }

        $migrations = array_map(
            fn ($path) => $this->sandboxPath($path),
            array_values(array_filter($created, fn ($path) => str_starts_with($path, 'database/migrations/')))
        );
        Artisan::call('migrate', ['--path' => $migrations, '--realpath' => true]);

        View::getFinder()->prependLocation($this->sandboxPath('resources/views'));

        Route::middleware(['web', 'lang'])->prefix('gload')->name('backend.')->group($this->sandboxPath('routes/backend.php'));
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();

        // The sandbox copies of AppServiceProvider and UploadDocumentService cannot be
        // loaded next to the real classes, so their injected entries are mirrored here.
        $this->assertStringContainsString("'gadget' => \\App\\Models\\Gadget::class,", file_get_contents($this->sandboxPath('app/Providers/AppServiceProvider.php')));
        $this->assertStringContainsString("'gadget_cover' => \$standardImage,", file_get_contents($this->sandboxPath('app/Services/UploadDocumentService.php')));
        $morphMap = Relation::morphMap();
        $this->beforeApplicationDestroyed(fn () => Relation::morphMap($morphMap, false));
        Relation::morphMap(['gadget' => Gadget::class]);
        $this->app->bind(UploadDocumentService::class, fn () => new class extends UploadDocumentService
        {
            public function __construct()
            {
                parent::__construct();

                $this->allowedTypes['gadget_cover'] = $this->allowedTypes['blog_images'];
            }
        });

        foreach (self::LOCALES as $locale) {
            $lines = require $this->sandboxPath("lang/{$locale}/backend.php");
            app('translator')->addLines(Arr::dot(Arr::only($lines, ['titles', 'labels']), 'backend.'), $locale);
        }
    }

    private function adminWithSeededPermissions(): Admin
    {
        $source = file_get_contents($this->sandboxPath('database/seeders/PermissionSeeder.php'));
        $seeder = $this->sandboxPath('SandboxPermissionSeeder.php');
        file_put_contents($seeder, str_replace('class PermissionSeeder', 'class SandboxPermissionSeeder', $source));
        require_once $seeder;

        $this->seed(SandboxPermissionSeeder::class);

        $this->assertTrue(Permission::where('name', 'gadgets delete')->exists());
        $this->assertTrue(Role::findByName('Admin', 'admin')->hasPermissionTo('gadgets index'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->adminWithRole('Admin', 'admin@example.com');
    }

    private function adminWithRole(string $roleName, string $email): Admin
    {
        $role = Role::findOrCreate($roleName, 'admin');

        $admin = Admin::create([
            'name' => $roleName,
            'email' => $email,
            'role_id' => $role->id,
            'status' => '1',
            'password' => 'password',
        ]);
        $admin->assignRole($role);

        return $admin;
    }
}

<?php

namespace Tests\Feature\CrudGenerator;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudGeneratorUiTest extends TestCase
{
    use InteractsWithSandbox;
    use RefreshDatabase;

    private Admin $developer;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSandbox();

        $this->developer = $this->makeAdmin(Admin::DEVELOPER_ROLE, 'developer@example.com');
        $this->admin = $this->makeAdmin('Admin', 'admin@example.com');
    }

    private function makeAdmin(string $roleName, string $email): Admin
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

    private function formData(array $overrides = []): array
    {
        $spec = $this->sampleSpec()->toArray();

        return array_merge([
            'name' => $spec['name'],
            'plural' => '',
            'label' => '',
            'icon' => '',
            'slug' => '1',
            'publish_date' => '1',
            'order' => '1',
            'status' => '1',
            'fields' => array_map(fn ($field) => [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => $field['required'] ? '1' : '0',
                'translatable' => $field['translatable'] ? '1' : '0',
                'quill' => $field['quill'] ? '1' : '0',
                'max_length' => $field['max_length'] ?? '',
            ], $spec['fields']),
        ], $overrides);
    }

    public static function generatorRoutes(): array
    {
        return [
            'index' => ['get', 'backend.crud_generator.index', []],
            'create' => ['get', 'backend.crud_generator.create', []],
            'preview' => ['post', 'backend.crud_generator.preview', []],
            'store' => ['post', 'backend.crud_generator.store', []],
            'show' => ['get', 'backend.crud_generator.show', ['module' => 'Gadget']],
        ];
    }

    #[DataProvider('generatorRoutes')]
    public function test_non_developers_get_403_on_every_route(string $method, string $route, array $parameters): void
    {
        $this->actingAs($this->admin, 'admin')->{$method}(route($route, $parameters))->assertForbidden();
    }

    #[DataProvider('generatorRoutes')]
    public function test_disabled_generator_returns_404_even_for_developers(string $method, string $route, array $parameters): void
    {
        config(['crud-generator.enabled' => false]);

        $this->actingAs($this->developer, 'admin')->{$method}(route($route, $parameters))->assertNotFound();
    }

    #[DataProvider('generatorRoutes')]
    public function test_guests_are_redirected_to_login(string $method, string $route, array $parameters): void
    {
        $this->{$method}(route($route, $parameters))->assertRedirect(route('backend.login.form'));
    }

    public function test_sidebar_link_is_rendered_only_for_developers_when_enabled(): void
    {
        $link = route('backend.crud_generator.index');

        $this->actingAs($this->developer, 'admin')->get(route('backend.dashboard'))->assertOk()->assertSee($link, false);
        $this->actingAs($this->admin, 'admin')->get(route('backend.dashboard'))->assertOk()->assertDontSee($link, false);

        config(['crud-generator.enabled' => false]);

        $this->actingAs($this->developer, 'admin')->get(route('backend.dashboard'))->assertOk()->assertDontSee($link, false);
    }

    public function test_index_and_create_pages_render(): void
    {
        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.crud_generator.index'))
            ->assertOk()
            ->assertSee(trans('backend.crud_generator.no_modules'));

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.crud_generator.create'))
            ->assertOk()
            ->assertSee('kt_crud_generator_field_template', false);
    }

    public function test_preview_lists_files_and_diffs_without_writing(): void
    {
        $before = $this->sandboxSnapshot();

        $response = $this->actingAs($this->developer, 'admin')
            ->post(route('backend.crud_generator.preview'), $this->formData());

        $response->assertOk()
            ->assertSee('app/Models/Gadget.php')
            ->assertSee('routes/backend.php')
            ->assertSee('crud-generator:start Gadget')
            ->assertSee('gadgets index, gadgets create, gadgets show, gadgets edit, gadgets delete');

        $this->assertSame($before, $this->sandboxSnapshot());
        $this->assertCount(1, session('crud_generator.specs'));
    }

    public function test_preview_rejects_reserved_and_conflicting_names(): void
    {
        $this->actingAs($this->developer, 'admin')
            ->from(route('backend.crud_generator.create'))
            ->post(route('backend.crud_generator.preview'), $this->formData(['name' => 'Blog']))
            ->assertRedirect(route('backend.crud_generator.create'))
            ->assertSessionHasErrors('name');

        $fields = $this->formData()['fields'];
        $fields[0]['name'] = 'created_at';

        $this->actingAs($this->developer, 'admin')
            ->from(route('backend.crud_generator.create'))
            ->post(route('backend.crud_generator.preview'), $this->formData(['fields' => $fields]))
            ->assertSessionHasErrors('fields.0.name');
    }

    public function test_confirm_generates_the_module_and_shows_the_result(): void
    {
        $this->actingAs($this->developer, 'admin')->post(route('backend.crud_generator.preview'), $this->formData());
        $token = array_key_first(session('crud_generator.specs'));

        $this->actingAs($this->developer, 'admin')
            ->post(route('backend.crud_generator.store'), ['token' => $token, 'migrate' => '0', 'seed' => '0'])
            ->assertRedirect(route('backend.crud_generator.show', ['module' => 'Gadget']));

        $this->assertFileExists($this->sandboxPath('app/Http/Controllers/Backend/GadgetController.php'));
        $this->assertStringContainsString('crud-generator:start Gadget', file_get_contents($this->sandboxPath('routes/backend.php')));

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.crud_generator.show', ['module' => 'Gadget']))
            ->assertOk()
            ->assertSee('app/Models/GadgetTranslation.php')
            ->assertSee('PermissionSeeder');

        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.crud_generator.index'))
            ->assertOk()
            ->assertSee('Gadget');
    }

    public function test_token_can_only_be_used_once(): void
    {
        $this->actingAs($this->developer, 'admin')->post(route('backend.crud_generator.preview'), $this->formData());
        $token = array_key_first(session('crud_generator.specs'));

        $this->actingAs($this->developer, 'admin')->post(route('backend.crud_generator.store'), ['token' => $token]);

        $this->actingAs($this->developer, 'admin')
            ->post(route('backend.crud_generator.store'), ['token' => $token])
            ->assertRedirect(route('backend.crud_generator.create'))
            ->assertSessionHas('error');
    }

    public function test_unknown_token_is_rejected(): void
    {
        $this->actingAs($this->developer, 'admin')
            ->post(route('backend.crud_generator.store'), ['token' => str_repeat('a', 40)])
            ->assertRedirect(route('backend.crud_generator.create'))
            ->assertSessionHas('error');

        $this->assertFileDoesNotExist($this->sandboxPath('app/Models/Gadget.php'));
    }

    public function test_unknown_module_returns_404(): void
    {
        $this->actingAs($this->developer, 'admin')
            ->get(route('backend.crud_generator.show', ['module' => 'Nope']))
            ->assertNotFound();
    }
}

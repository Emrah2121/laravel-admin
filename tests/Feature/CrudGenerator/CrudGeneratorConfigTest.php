<?php

namespace Tests\Feature\CrudGenerator;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudGeneratorConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_is_disabled_by_default_outside_local(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertFalse(config('crud-generator.enabled'));
    }

    public function test_default_environment_disables_the_ui_for_developers(): void
    {
        $role = Role::findOrCreate(Admin::DEVELOPER_ROLE, 'admin');
        $developer = Admin::create([
            'name' => 'Developer',
            'email' => 'developer@example.com',
            'role_id' => $role->id,
            'status' => '1',
            'password' => 'password',
        ]);

        $this->assertFalse($developer->canUseCrudGenerator());

        $this->actingAs($developer, 'admin')
            ->get(route('backend.crud_generator.index'))
            ->assertNotFound();
    }

    public function test_artisan_runs_are_limited_to_configured_environments(): void
    {
        $this->assertSame(['local', 'staging'], config('crud-generator.artisan_environments'));
    }
}

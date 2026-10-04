<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('guard_name', 'admin')->delete();

        $modules = [
            'admins',
            'roles',
            'permissions',
            'languages',
            'translations',
            'settings',
            'blogs',
            'logs',
        ];

        $actions = [
            'index',
            'create',
            'show',
            'edit',
            'delete',
        ];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$module} {$action}",
                    'guard_name' => 'admin',
                ]);
            }
        }

        $adminRole = Role::findOrCreate('Admin', 'admin');

        $adminRole->syncPermissions(
            Permission::where('guard_name', 'admin')->get()
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('All permissions deleted and recreated successfully!');
    }
}

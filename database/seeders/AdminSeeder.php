<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate('Admin', 'admin');

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'role_id' => $role->id,
                'status' => '1',
                'password' => 'password',
            ]
        );

        $admin->assignRole($role);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate(Admin::DEVELOPER_ROLE, 'admin');

        Admin::updateOrCreate(
            ['email' => 'developer@gmail.com'],
            [
                'name' => 'Developer',
                'role_id' => $role->id,
                'status' => '1',
                'password' => 'password',
            ]
        );
    }
}

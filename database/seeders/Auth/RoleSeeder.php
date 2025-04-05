<?php

namespace Database\Seeders\Auth;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'is_system' => 1,
        ]);
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'is_system' => 1,
    ]);

    }
}

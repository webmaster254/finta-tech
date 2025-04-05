<?php

namespace Database\Seeders\Auth;

use App\Models\ClientType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ClientTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClientType::create([
            'name' => 'client',

        ]);

    }
}

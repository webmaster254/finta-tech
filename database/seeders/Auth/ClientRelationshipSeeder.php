<?php

namespace Database\Seeders\Auth;

use App\Models\ClientType;
use Illuminate\Database\Seeder;
use App\Models\ClientRelationship;
use Spatie\Permission\Models\Role;

class ClientRelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClientRelationship::create([
            'name' => 'Workmate',

        ]);

    }
}

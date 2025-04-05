<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::create([

            'first_name' => 'John',
            'middle_name' => 'Doe',
            'last_name' => 'Doe',
            'account_number' => '12345678',
            'gender' => 'male',
            'marital_status' => 'single',
            'profession_id' => 1,
            'client_type_id' => 1,
            'status' => 'active',
            'created_by_id' => 1,
            'loan_officer_id' => 1,
            'mobile' => '0700000000',]);

    }
}

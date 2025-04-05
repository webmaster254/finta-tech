<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;



class ProfessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Profession::create([
            'name' => 'Doctor',
        ]);

        Profession::create([
            'name' => 'Entrepreneur',
        ]);

        Profession::create([
            'name' => 'Engineer',
        ]);

        Profession::create([
            'name' => 'Teacher',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Title;
use Illuminate\Database\Seeder;



class TitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Title::create([
            'name' => 'Mr',
        ]);

        Title::create([
            'name' => 'Mrs',
        ]);

        Title::create([
            'name' => 'Ms',
        ]);

        
    }
}

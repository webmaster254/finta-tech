<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::create([
            'is_default' => 1,
            'name' => 'Kenyan Shillings',
            'code' => 'KSH',
            'symbol' => 'KES',
            'position' => 'left',
        ]);
    }
}

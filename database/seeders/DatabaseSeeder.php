<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\TitleSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\Auth\RoleSeeder;
use Database\Seeders\Auth\UserSeeder;
use Database\Seeders\ProfessionSeeder;
use Database\Seeders\KenyaCountySeeder;
use Database\Seeders\Auth\ClientTypeSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\LoanProcessingStrategySeeder;
use Database\Seeders\Auth\ClientRelationshipSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ClientRelationshipSeeder::class);

        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(ShieldSeeder::class);
        $this->call(ClientTypeSeeder::class);
        $this->call(ClientRelationshipSeeder::class);
        $this->call(LoanProcessingStrategySeeder::class);
        $this->call(TitleSeeder::class);
        $this->call(ChartOfAccountsSeeder::class);
       $this->call(KenyaCountySeeder::class);
       $this->call(ProfessionSeeder::class);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


    }
}

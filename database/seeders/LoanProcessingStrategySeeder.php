<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use App\Models\Loan\LoanTransactionProcessingStrategy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LoanProcessingStrategySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoanTransactionProcessingStrategy::create([
            'name' => 'Penalties, Fees, Interest, Principal order',
            'active' => true,
        ]);

        LoanTransactionProcessingStrategy::create([
            'name' => 'Principal, Interest, Penalties, Fees Order',
            'active' => true,
        ]);

        LoanTransactionProcessingStrategy::create([
            'name' => 'Interest, Principal, Penalties, Fees Orde',
            'active' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Enums\ChartAccountType;
use Illuminate\Database\Seeder;
use App\Models\ChartOfAccountSubtype;
use App\Events\DefaultAccountsCreated;
use App\Utilities\Currency\CurrencyAccessor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$branches = Branch::all();

foreach ($branches as $branch) {
    event(new DefaultAccountsCreated($branch));
}
    }


}

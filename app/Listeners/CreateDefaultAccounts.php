<?php

namespace App\Listeners;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use Filament\Facades\Filament;
use App\Enums\ChartAccountType;
use App\Models\ChartOfAccountSubtype;
use App\Events\DefaultAccountsCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Utilities\Currency\CurrencyAccessor;

class CreateDefaultAccounts implements ShouldQueue
{

    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
//
    }

    /**
     * Handle the event.
     */
    public function handle(DefaultAccountsCreated $event): void
    {
        $branch = $event->branch;

    $chartOfAccounts = config('chart-of-accounts.default');


    foreach ($chartOfAccounts as $type => $subtypes) {

        foreach ($subtypes as $subtypeName => $subtypeConfig) {
            $subtype = ChartOfAccountSubtype::create([
                'branch_id' => $branch->id,
                'category' => ChartAccountType::from($type)->getCategory()->value,
                'type' => $type,
                'name' => $subtypeName,
                'description' => $subtypeConfig['description']?? 'No description available.',
            ]);

            $this->createDefaultAccounts($subtype, $subtypeConfig,$branch);
        }
    }
}

private function createDefaultAccounts(ChartOfAccountSubtype $subtype, array $subtypeConfig,Branch $branch)
{



    if (isset($subtypeConfig['accounts']) && is_array($subtypeConfig['accounts'])) {
        $baseCode = $subtypeConfig['base_code'];
    foreach ($subtypeConfig['accounts'] as $accountName => $accountDetails) {

        ChartOfAccount::create([
            'branch_id' =>  $branch->id,
            'subtype_id' => $subtype->id,
            'gl_code' => $baseCode++,
            'name' => $accountName,
            'currency_code' => CurrencyAccessor::getDefaultCurrency(),
            'description' => $accountDetails['description']?? 'No description available.',
            'account_type' => $subtype->type,
            'category' => $subtype->category,
            'active' => true,
            'default' => true,
        ]);
    }
}
}
    }


<?php

namespace App\Listeners;

use App\Models\BankAccount;
use App\Events\InvestmentReversed;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\InvestmentPaymentSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;

class UndoInvestment
{
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
    public function handle(InvestmentReversed $event): void
    {
        //reverse investment
        $event->investor->update(
            [
                'status' => 'approved',

        ]);
        //delete payment schedule
        InvestmentPaymentSchedule::where('investment_id', $event->investor->id)->delete();
        //delete the funds from bank account
        $bankAccount = BankAccount::where('chart_of_account_id', 1)->first();
        $bankAccount->balance -= $event->investor->investment_amount;
        $bankAccount->save();



    }
}

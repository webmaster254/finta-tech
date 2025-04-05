<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Models\BankAccount;
use App\Events\InvestmentMade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\InvestmentPaymentSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneratePaymentSchedule implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  \App\Events\InvestmentMade  $event
     * @return void
     */
    public function handle(InvestmentMade $event)
    {

        $investmentAmount = $event->investor->investment_amount;
        $investmentDuration = ($event->investor->investment_duration)*12;
        $investmentReturn = ($event->investor->investment_return)/100;
        $investmentDate = $event->investor->investment_date;


        $monthlyPayment = ($investmentAmount * $investmentReturn);


        $startDate = Carbon::parse($investmentDate)->addMonths(2);

        for ($i = 0; $i < $investmentDuration; $i++) {
            $paymentDate = $startDate->copy()->addMonths($i);



            InvestmentPaymentSchedule::create([
                'investment_id' => $event->investor->id,
                'payment_date' => $paymentDate,
                'amount' => $monthlyPayment,
                'installment' => $i+1
            ]);

        }
         //change status to active
         $event->investor->update(
            [
                'status' => 'active',
                'invested_by_id' => Auth::id(),
            ]);
         //add invested amount  to bank account
            $bankAccount = BankAccount::where('chart_of_account_id', 1)->first();
            $bankAccount->balance += $investmentAmount;
            $bankAccount->save();
    }
}

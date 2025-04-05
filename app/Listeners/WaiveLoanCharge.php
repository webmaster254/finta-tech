<?php

namespace App\Listeners;

use App\Events\LoanChargeWaive;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;

class WaiveLoanCharge
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
    public function handle(LoanChargeWaive $event): void
    {
            $loan_linked_charge = $event->charges;
            $loan_linked_charge->waived = 1;
            $loan_linked_charge->save();
            $loan = $loan_linked_charge->loan;
            $loan_id = $loan_linked_charge->loan_id;
            $loan_transaction = $loan_linked_charge->transaction;
            $loan_transaction->credit = $loan_transaction->amount;
            $loan_transaction->debit = $loan_transaction->amount;
            $loan_transaction->reversed = 1;
            $loan_transaction->save();
            if ($loan_linked_charge->loan_charge_type_id == 2 || $loan_linked_charge->loan_charge_type_id == 4 || $loan_linked_charge->loan_charge_type_id == 6 || $loan_linked_charge->loan_charge_type_id == 2 || $loan_linked_charge->loan_charge_type_id == 7 || $loan_linked_charge->loan_charge_type_id == 8) {
                $repayment_schedule = LoanRepaymentSchedule::where('loan_id', $loan_id)->where('due_date', $loan_transaction->due_date)->first();
                if ($loan_linked_charge->is_penalty == 1) {
                    $repayment_schedule->penalties_waived_derived = $repayment_schedule->penalties_waived_derived + $loan_linked_charge->calculated_amount;
                } else {
                    $repayment_schedule->fees_waived_derived = $repayment_schedule->fees_waived_derived + $loan_linked_charge->calculated_amount;
                }
                $repayment_schedule->save();
            }
            if ($loan_linked_charge->loan_charge_type_id == 3) {
                $amount = 0;
                foreach ($loan->repayment_schedules as $repayment_schedule) {
                    if ($loan_linked_charge->loan_charge_option_id == 1) {
                        $amount = $loan_linked_charge->calculated_amount;
                    }
                    if ($loan_linked_charge->loan_charge_option_id == 2) {
                        $amount = round(($loan_linked_charge->amount * $repayment_schedule->principal / 100), $loan->decimals);
                    }
                    if ($loan_linked_charge->loan_charge_option_id == 3) {
                        $amount = round(($loan_linked_charge->amount * ($repayment_schedule->interest + $repayment_schedule->principal) / 100), $loan->decimals);
                    }
                    if ($loan_linked_charge->loan_charge_option_id == 4) {
                        $amount = round(($loan_linked_charge->amount * $repayment_schedule->interest / 100), $loan->decimals);
                    }
                    if ($loan_linked_charge->loan_charge_option_id == 5) {
                        $amount = round(($loan_linked_charge->amount * $loan->principal / 100), $loan->decimals);
                    }
                    if ($loan_linked_charge->loan_charge_option_id == 6) {
                        $amount = round(($loan_linked_charge->amount * $loan->principal / 100), $loan->decimals);
                    }
                    if ($loan_linked_charge->loan_charge_option_id == 7) {
                        $amount = round(($loan_linked_charge->amount * $loan->principal / 100), $loan->decimals);
                    }
                    $repayment_schedule->fees_waived_derived = $repayment_schedule->fees_waived_derived + $amount;
                    $repayment_schedule->save();
                }
            }
    
    }
}

<?php

namespace App\Listeners;

use App\Models\Transaction;
use App\Models\JournalEntry;
use App\Models\JournalEntries;
use App\Events\LoanUndisbursed;
use App\Models\Loan\LoanTransaction;
use App\Models\Loan\LoanLinkedCharge;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;

class UndisburseLoanSchedule
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
    public function handle(LoanUndisbursed $event): void
    {
        $loan = $event->loan;
        $loanTransaction = LoanTransaction::where('loan_id', $loan->id)->first();



        if ($loanTransaction) {
            LoanLinkedCharge::where('loan_id', $loan->id)->update(["loan_transaction_id" => null]);
            LoanRepaymentSchedule::where('loan_id', $loan->id)->delete();
            Transaction::where('loan_id', $loan->id)->delete();
            JournalEntries::where('transaction_id', $loanTransaction->id)->delete();
            $transactionNumber = 'L' . $loanTransaction->id;
            //JournalEntry::where('transaction_number', $transactionNumber)->delete();
            LoanTransaction::where('loan_id', $loan->id)->delete();
        }
    }
}

<?php
namespace App\Jobs;

use App\Models\Message;
use App\Models\Transaction;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use App\Models\JournalEntries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Http;
use App\Models\Loan\LoanLinkedCharge;
use Modules\Setting\Entities\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UndisburseLoanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $loan;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($loan)
    {
        $this->loan = $loan;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $loan = $this->loan;
        $loanTransaction = LoanTransaction::where('loan_id', $loan->id)->first();



        if ($loanTransaction) {
            LoanLinkedCharge::where('loan_id', $loan->id)->delete();
            LoanRepaymentSchedule::where('loan_id', $loan->id)->delete();
            Transaction::where('loan_id', $loan->id)->delete();
            JournalEntries::where('transaction_id', $loanTransaction->id)->delete();
            $transactionNumber = 'L' . $loanTransaction->id;
            LoanTransaction::where('loan_id', $loan->id)->delete();
        }
        
    }

       
}
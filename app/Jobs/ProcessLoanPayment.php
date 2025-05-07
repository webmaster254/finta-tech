<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\Client;
use App\Models\MpesaC2B;
use App\Events\LoanRepayment;
use Illuminate\Bus\Queueable;
use App\Jobs\UpdateTransactionsJob;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\SendPaymentSmsNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessLoanPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $response;
    /**
     * Create a new job instance.
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

       //first check if client exists
        $client = Client::where('hashed_mobile', $this->response['MSISDN'])
                        ->first();

        if (!$client){
             //update payment status to not resolved
             MpesaC2B::where('Transaction_ID', $this->response['TransID'])
             ->update(['status' => 'not_resolved']);
            return;
        }

        //first save the money to client account
        $clientAccount = $client->account;
        $transaction = $clientAccount->deposit($this->response['TransAmount'],'deposit','Mpesa deposit-'.$this->response['TransID'],[                                  // Additional metadata
            'reference_number' => $this->response['TransID'],
            'payment_method' => 'mpesa',
            'posted_by' => 'system Api',
            'transaction_id' => $this->response['TransID']
        ]);
        
                        
        $clientWithActiveLoan = Client::where('hashed_mobile', $this->response['MSISDN'])
                        ->where('status', 'active')
                        ->whereHas('branch', function ($query) {
                            $query->whereIn('id', Branch::pluck('id'));
                        })
                        ->with(['loans' => function ($query) {
                            $query->where('status', 'active')
                                ->with(['repayment_schedules', 'transactions', 'loan_product']);
                        }])
                        ->first();




            if (!$clientWithActiveLoan){
                //update payment status to not resolved
                MpesaC2B::where('Transaction_ID', $this->response['TransID'])
                            ->update(['status' => 'not_resolved']);

            } else {

                $loandata = $clientWithActiveLoan->loans->first();
                
                if(!$loandata){
                    //update payment status to not resolved
                    MpesaC2B::where('Transaction_ID', $this->response['TransID'])
                                ->update(['status' => 'not_resolved']);
                    return;
                }

                MpesaC2B::where('Transaction_ID', $this->response['TransID'])
                        ->update(['status' => 'resolved']);
                
                //withdraw fund from client account to repay loan
                $withdrawTransaction = $clientAccount->withdraw($this->response['TransAmount'],'withdraw','Loan repayment-'.$loandata->loan_account_number,[                                  // Additional metadata
                    'reference_number' => $this->response['TransID'],
                    'payment_method' => 'mpesa',
                    'posted_by' => 'system Api',
                    'transaction_id' => $this->response['TransID']
                ]);

                $loanTransactionData = [
                            'created_by_id' => Auth::id(),
                            'loan_id' => $loandata->id,
                            'branch_id' => 1,
                            'first_name' => $this->response['FirstName'],
                            'payment_detail_id' => 1,
                            'name' => 'Repayment',
                            'loan_transaction_type_id' => 2,
                            'online_transaction' => 1,
                            'gateway_id' => 3,
                            'status' => 'approved',
                            'submitted_on' => date("Y-m-d"),
                            'created_on' => date("Y-m-d"),
                            'account_number' => $this->response['BillRefNumber'],
                            'reference' => $this->response['TransID'],
                            'amount' => $this->response['TransAmount'],
                            'credit' => $this->response['TransAmount'],
                        ];

              //loan transaction
              $this->loanTransactions($loandata,$this->response);
              //fire transaction updated event
              //event(new LoanRepayment($loandata,$loanTransactionData));
              //update payment status to resolved
              UpdateTransactionsJob::dispatch($loandata,$loanTransactionData)->onQueue('default');

              $total_due = $loandata->repayment_schedules->sum('total_due');
              SendPaymentSmsNotificationJob::dispatch($clientWithActiveLoan,$loandata,$total_due,$this->response['TransAmount'])->onQueue('default');

                }

    }

        public function loanTransactions($loan,array $response)
        {

                    $loan_transaction = new LoanTransaction();
                    $loan_transaction->created_by_id = Auth::id();
                    $loan_transaction->loan_id = $loan->id;
                    $loan_transaction->branch_id = 1;
                    $loan_transaction->payment_detail_id = 1;
                    $loan_transaction->first_name = $response['FirstName'];
                    $loan_transaction->name = 'Repayment';
                    $loan_transaction->loan_transaction_type_id = 2;
                    $loan_transaction->online_transaction = 1;
                    $loan_transaction->gateway_id = 3;
                    $loan_transaction->status = 'approved';
                    $loan_transaction->submitted_on = date("Y-m-d");
                    $loan_transaction->created_on = date("Y-m-d");
                    $loan_transaction->account_number = $response['BillRefNumber'];
                    $loan_transaction->reference = $response['TransID'];
                    $loan_transaction->amount = $response['TransAmount'];
                    $loan_transaction->credit = $response['TransAmount'];
                    $loan_transaction->save();


                }



}

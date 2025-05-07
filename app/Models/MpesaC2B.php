<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Events\LoanRepayment;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MpesaC2B extends Model
{
    use HasFactory;

    protected $fillable = [
        'Transaction_type','Transaction_ID','Transaction_Time','Amount','Business_Shortcode',
        'Account_Number','status','Organization_Account_Balance','ThirdParty_Transaction_ID',
        'Phonenumber','FirstName', 'MiddleName', 'LastName'
    ];
    protected $table = 'mpesa_c2b';

    // protected $casts = [
    //     'Amount' => MoneyCast::class,
    // ];
    
     public static function transactionExists($transactionId)
    {
        return self::where('Transaction_ID', $transactionId)->exists();
    }

    public function resolve(MpesaC2B $record): void
    {
        $record->update([
            'status' => 'resolved',
        ]);
    }

    public function updateTransactions($loan,$record,array $data): void
    {


        $loanTransactionData = [
            'created_by_id' => Auth::id(),
            'loan_id' => $record->id,
            'branch_id' => 1,
            'first_name' => $record->FirstName,
            'payment_detail_id' => 1,
            'name' => 'Repayment',
            'loan_transaction_type_id' => 2,
            'online_transaction' => 1,
            'gateway_id' => 3,
            'status' => 'approved',
            'submitted_on' => $record->Transaction_Time,
            'created_on' => date("Y-m-d"),
            'account_number' => $record->Account_number,
            'reference' => $record->Transaction_ID,
            'amount' => $record->Amount,
            'credit' => $record->Amount,
        ];
        $loan_transaction = new LoanTransaction();
                $loan_transaction->created_by_id = Auth::user()->id;
                $loan_transaction->loan_id = $loan->id;
                $loan_transaction->branch_id = 1;
                $loan_transaction->first_name=$record->FirstName;
                $loan_transaction->payment_detail_id = 1;
                $loan_transaction->name = 'Repayment';
                $loan_transaction->loan_transaction_type_id = 2;
                $loan_transaction->online_transaction = 1;
                $loan_transaction->gateway_id = 3;
                $loan_transaction->status = 'approved';
                $loan_transaction->submitted_on = date("Y-m-d");
                $loan_transaction->created_on = date("Y-m-d");
                $loan_transaction->account_number = $data['account_number'];
                $loan_transaction->reference = $record->Transaction_ID;
                $loan_transaction->amount = $record->Amount;
                $loan_transaction->credit = $record->Amount;
                $loan_transaction->save();

                event(new LoanRepayment($loan,$loanTransactionData));

                $record->update([
                    'status' => 'resolved',
                ]);
    }
}

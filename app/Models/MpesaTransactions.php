<?php

namespace App\Models;

use App\Events\LoanRepayment;
use Illuminate\Support\Facades\Log;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MpesaTransactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'transaction_date',
        'msisdn',
        'sender',
        'transaction_type',
        'bill_reference',
        'amount',
        'organization_name',
        'response_ref_id',
        'response_code',
        'status',
        'response_message',
        'raw_response'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'raw_response' => 'array'
    ];

    /**
     * Create a new transaction from Mpesa API response
     */
    public static function createFromResponse(array $transaction, array $response)
    {
         if (empty($transaction)) {
            throw new \Exception('Invalid transaction data structure');
        }

        return self::create([
            'transaction_id' => $transaction['transactionId'] ?? null,
            'transaction_date' => $transaction['trxDate'] ?? null,
            'msisdn' => $transaction['msisdn'] ?? null,
            'sender' => $transaction['sender'] ?? null,
            'transaction_type' => $transaction['transactiontype'] ?? null,
            'bill_reference' => $transaction['billreference'] ?? null,
            'amount' => $transaction['amount'] ?? null,
            'organization_name' => $transaction['organizationname'] ?? null,
            'response_ref_id' => $response['ResponseRefID'] ?? null,
            'response_code' => $response['ResponseCode'] ?? null,
            'response_message' => $response['ResponseMessage'] ?? null,
            'status' => 'not_resolved',
            'raw_response' => $response
        ]);
    }

    public function updateTransactions($loan,$record,array $data): void
    {

        $loanTransactionData = [
            'created_by_id' => Auth::id(),
            'loan_id' => $record->id,
            'branch_id' => 1,
            'first_name' => $record->FirstName??null,
            'payment_detail_id' => 1,
            'name' => 'Repayment',
            'loan_transaction_type_id' => 2,
            'online_transaction' => 1,
            'gateway_id' => 3,
            'status' => 'approved',
            'submitted_on' => $record->transaction_date,
            'created_on' => date("Y-m-d"),
            'account_number' => $data['account_number'],
            'reference' => $record->transaction_id,
            'amount' => $record->amount,
            'credit' => $record->amount,
        ];


        $loan_transaction = new LoanTransaction();
                $loan_transaction->created_by_id = Auth::user()->id;
                $loan_transaction->loan_id = $loan->id;
                $loan_transaction->branch_id = 1;
                $loan_transaction->first_name=$record->FirstName??null;
                $loan_transaction->payment_detail_id = 1;
                $loan_transaction->name = 'Repayment';
                $loan_transaction->loan_transaction_type_id = 2;
                $loan_transaction->online_transaction = 1;
                $loan_transaction->gateway_id = 3;
                $loan_transaction->status = 'approved';
                $loan_transaction->submitted_on = date("Y-m-d");
                $loan_transaction->created_on = date("Y-m-d");
                $loan_transaction->account_number = $data['account_number'];
                $loan_transaction->reference = $record->transaction_id;
                $loan_transaction->amount = $record->amount;
                $loan_transaction->credit = $record->amount;
                $loan_transaction->save();

                event(new LoanRepayment($loan,$loanTransactionData));

                $record->update([
                    'status' => 'resolved',
                ]);
    }
}

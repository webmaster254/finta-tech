<?php

namespace App\Models\Loan;

use App\Models\User;
use App\Models\Client;
use App\Casts\MoneyCast;
use App\Models\PaymentDetail;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'loan_id',
        'payment_detail_id',
        'first_name',
        'account_number',
        'name',
        'amount',
        'created_by_id',
        'credit',
        'debit',
        'principal_repaid_derived',
        'interest_repaid_derived',
        'fee_repaid_derived',
        'penalties_repaid_derived',
        'loan_transaction_type_id',
        'reversed',
        'reversible',
        'submitted_on',
        'due_date',
        'created_on',
        'status',
        'reference',
        'gateway_id',
        'description',
        'payment_gateway_data',
        'online_transaction',
        'branch_id',

    ];
    public $table = "loan_transactions";

    // protected $casts = [
    //     'amount' => MoneyCast::class,
    // ];
    public static function transactionExists($transactionId)
    {
        return self::where('reference', $transactionId)->exists();
    }

    public function payment_detail()
    {
        return $this->hasOne(PaymentDetail::class, 'id', 'payment_detail_id');
    }

    public function loan()
    {
        return $this->hasOne(Loan::class, 'id', 'loan_id');
    }

    public function created_by()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id');
    }
    public function account()
    {
        return $this->hasOne(Client::class);
    }


}

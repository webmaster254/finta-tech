<?php

namespace App\Models\Loan;

use App\Models\Loan\LoanCharge;
use App\Models\Loan\LoanTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanLinkedCharge extends Model
{
    use HasFactory;
    protected $fillable = [
        'loan_id',
        'loan_charge_id',
        'loan_transaction_id',
        'name',
        'amount',
        'account_number',
        'calculated_amount',
        'amount_paid_derived',
        'amount_waived_derived',
        'amount_written_off_derived',
        'amount_outstanding_derived',
        'is_penalty',
        'waived',
        'is_paid',

    ];
    public $table = "loan_linked_charges";

    public function charge()
    {
        return $this->hasOne(LoanCharge::class, 'id', 'loan_charge_id');
    }

    public function loans()
    {
        return $this->hasOne(Loan::class, 'id', 'loan_id');
    }

    public function transaction()
    {
        return $this->hasOne(LoanTransaction::class, 'id', 'loan_transaction_id');
    }



}

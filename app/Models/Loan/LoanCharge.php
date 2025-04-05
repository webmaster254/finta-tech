<?php

namespace App\Models\Loan;

use App\Models\Loan\LoanProduct;
use App\Models\Loan\LoanChargeType;
use App\Models\Loan\LoanChargeOption;
use App\Models\Loan\LoanLinkedCharge;
use Illuminate\Database\Eloquent\Model;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanCharge extends Model
{
    use HasFactory;
    protected $fillable = [
        'currency_id',
        'loan_charge_type_id',
        'loan_charge_option_id',
        'name',
        'amount',
        'schedule',
        'schedule_frequency',
        'schedule_frequency_type',
        'is_penalty',
        'active',
    ];
    public $table = "loan_charges";

    public function charge_type()
    {
        return $this->HasOne(LoanChargeType::class, 'id', 'loan_charge_type_id');
    }
    public function charge_option()
    {
        return $this->HasOne(LoanChargeOption::class, 'id', 'loan_charge_option_id');
    }

    public function loanProducts()
    {
        return $this->belongsToMany(LoanProduct::class, 'loan_product_linked_charges')->withPivot('id')->withTimestamps();
    }


 
}

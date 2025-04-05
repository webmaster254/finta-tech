<?php

namespace App\Models\Loan;

use App\Models\Loan\LoanCharge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanProductLinkedCharge extends Model
{
    use HasFactory;
    protected $fillable = [
        'loan_product_id',
    ];
    public $table = "loan_product_linked_charges";

    public function charge()
    {
        return $this->hasOne(LoanCharge::class, 'id', 'loan_charge_id');
    }
}

<?php

namespace App\Models\Loan;

use App\Models\Loan\LoanCollateralType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanCollateral extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'created_by_id',
        'loan_collateral_type_id',
        'description',
        'value',
        'file',
        'status',
    ];
    public $table = "loan_collaterals";

    public function collateral_type()
    {
        return $this->belongsTo(LoanCollateralType::class, 'loan_collateral_type_id','id');
    }
}

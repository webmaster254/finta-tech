<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCollateralType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];
    public $table = "loan_collateral_types";
    public $timestamps = false;
}

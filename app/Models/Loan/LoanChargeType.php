<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanChargeType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'active',
    ];
    public $table = "loan_charge_types";
}

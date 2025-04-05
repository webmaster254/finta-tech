<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPurpose extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];
    public $table = "loan_purposes";
}

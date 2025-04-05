<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanOfficerHistory extends Model
{
    use HasFactory;

    public $table = 'loan_officer_history';
    protected $fillable = [
        'loan_id',
        'created_by_id',
        'loan_officer_id',
        'start_date',
        'end_date',
    ];
}

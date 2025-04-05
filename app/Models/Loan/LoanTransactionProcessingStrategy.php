<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanTransactionProcessingStrategy extends Model
{
    use HasFactory;
    protected $table = "loan_transaction_processing_strategies";
    
    protected $fillable = [
        'name',
        'active',
        
    ];

}

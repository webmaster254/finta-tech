<?php

namespace App\Models\Loan;

use App\Models\Loan\Loan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepaymentSchedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'created_by_id',
        'loan_id', 
        'paid_by_date', 
        'from_date', 
        'due_date', 
        'installment', 
        'principal', 
        'principal_repaid_derived', 
        'principal_written_off_derived',
        'interest',
        'interest_repaid_derived',
        'interest_written_off_derived',
        'interest_waived_derived',
        'fees',
        'fees_repaid_derived', 
        'fees_written_off_derived',
        'fees_waived_derived',
        'penalties', 
        'penalties_repaid_derived',
        'penalties_written_off_derived',
        'penalties_waived_derived', 
        'total_due',
        'month',
        'year',
    ];
    public $table = "loan_repayment_schedules";

    public function loan()
    {
        return $this->hasOne(Loan::class, 'id', 'loan_id');
    }
    public function getRepaymentStatus()
    {  
        if($this->due_date < Carbon::now())
        {
            if ($this->paid_by_date !== null &&$this->paid_by_date >= $this->due_date  ) {
                return 'On time Payment';
              } else {
                return 'Late Payment';
              }  
            
              
        }
        
    }

    public function getDueDate()
    {
        return $this->due_date;

    }
    public function getPaidByDate()
    {
        if ($this->paid_by_date !== null ) {
            return $this->paid_by_date;
        }  elseif($this->due_date < Carbon::now() && $this->total_due > 0) {
            return 'Overdue';
        }
    } 

    public function isOverdue() 
    {
    return $this->due_date < Carbon::now() && $this->total_due > 0;
    }
    public function getTotalPaid()
    {
        $total = $this->principal_repaid_derived + $this->interest_repaid_derived + $this->fees_repaid_derived + $this->penalties_repaid_derived;
        return $total;
    }
    public function getTotalInstallment()
    {
        $total = $this->principal + $this->interest ;
        return $total;
    }
    // public function getAmountRepaid()
    // {
    //     $total = $this->principal_repaid_derived + $this->interest_repaid_derived + $this->fees_repaid_derived + $this->penalties_repaid_derived;
    //     return $total;
    // }
    
}

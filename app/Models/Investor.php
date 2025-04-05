<?php

namespace App\Models;

use App\Models\User;
use App\Enums\Status;
use App\Enums\InvestorStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\InvestmentPaymentSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Investor extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'id_number',
        'address',
        'email',
        'bank_account',
        'bank_name',
        'bank_branch',
        'investment_amount',
        'investment_date',
        'investment_duration',
        'investment_duration_type',
        'investment_return',
        'status',
        'approved_by_id',
        'rejected_by_id',
        'invested_by_id',
        'avatar'
    ];


    // protected $casts = [
    //     'status' => InvestorStatus::class
    //    ];

    public function investment()
    {
        return $this->hasMany(InvestmentPaymentSchedule::class, 'investment_id', 'id')->orderBy('payment_date', 'asc');
    }
    public function approved_by()
       {
           return $this->belongsTo(User::class,'approved_by_id');
       }

    public function rejected_by()
       {
           return $this->belongsTo(User::class,'rejected_by_id');
       }

    public function invested_by()
       {
           return $this->belongsTo(User::class,'invested_by_id');
       }
}

<?php

namespace App\Models;

use App\Models\Investor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestmentPaymentSchedule extends Model
{
    use HasFactory;
    protected $fillable = ['investment_id', 'payment_date', 'amount','paid_amount','installment'];

    public function investment()
    {
        return $this->belongsTo(Investor::class);
    }
}

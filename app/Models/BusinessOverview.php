<?php

namespace App\Models;

use App\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessOverview extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'current_stock',
        'operating_capital',
        'average_weekly_sales',
        'average_weekly_purchase',
        'average_weekly_stock_balance',
        'cost_of_sales',
        'house_rent',
        'hs_electricity',
        'hs_food',
        'hs_transport',
        'clothings',
        'school_fees',
        'hs_total',
        'bs_rent',
        'bs_electricity',
        'bs_license',
        'bs_transport',
        'bs_wages',
        'bs_contributions',
        'bs_loan_repayment',
        'bs_other_drawings',
        'bs_spoilts_goods',
        'owner_salary',
        'bs_total',
        'gross_profit',
        'net_profit',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }
}

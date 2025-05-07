<?php

namespace App\Models;

use App\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessOverview extends Model
{
    use HasFactory;
    
    protected static function booted(): void
    {
        static::saved(function ($businessOverview) {
            // Calculate loan affordability and update client's suggested loan limit
            $businessOverview->calculateLoanAffordability();
        });
    }

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
        'affordability',
        'mpesa_statement',
        'mpesa_code',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }
    
    /**
     * Calculate loan affordability based on business net profit and update client's suggested loan limit
     * 
     * Formula:
     * 1. Take 75% of weekly net profit as affordable weekly installment
     * 2. Convert to daily installment (weekly / 7)
     * 3. Calculate monthly payable (daily * 30)
     * 4. Calculate principal (monthly payable - 30% interest)
     * 5. Update client's suggested loan limit with the calculated principal
     */
    public function calculateLoanAffordability(): void
    {
        // Check if net_profit is available
        if (!$this->net_profit) {
            return;
        }
        
        // Weekly net profit
        $weeklyNetProfit = $this->net_profit;
        
        // Calculate 75% of weekly net profit (affordable weekly installment)
        $affordableWeeklyInstallment = $weeklyNetProfit * 0.75;
        
        // Calculate affordable daily installment
        $affordableDailyInstallment = $affordableWeeklyInstallment / 7;
        
        // Calculate monthly payable (P+I)
        $monthlyPayable = $affordableDailyInstallment * 30;
        
        // Calculate principal (P = monthly payable - 30% interest)
        $interestAmount = $monthlyPayable * 0.30;
        $principal = $monthlyPayable - $interestAmount;
        
        // Round to nearest 100
        $suggestedLoanLimit = round($principal, -2);
        
        // Update the client's suggested loan limit
        $business = $this->business;
        if ($business && $business->client) {
            $business->client->update([
                'suggested_loan_limit' => $suggestedLoanLimit
            ]);
        }
    }
}

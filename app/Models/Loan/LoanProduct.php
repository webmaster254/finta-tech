<?php

namespace App\Models\Loan;

use App\Models\Fund;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Loan\LoanCharge;
use Illuminate\Database\Eloquent\Model;
use App\Models\Loan\LoanTransactionProcessingStrategy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanProduct extends Model
{
    use HasFactory;

    public $table = "loan_products";
    protected $fillable = [
        'name',
        'product_code',
        'description',
        'currency_id',
        'loan_transaction_processing_strategy_id',
        'fund_id',
        'fund_source_chart_of_account_id',
        'loan_portfolio_chart_of_account_id',
        'administration_fees_chart_of_account_id',
        'insurance_chart_of_account_id',
        'sms_charges_chart_of_account_id',
        'loan_extension_fees_chart_of_account_id',
        'penalties_chart_of_account_id',
        'bad_debts_provision_chart_of_account_id',
        'write_offs_chart_of_account_id',
        'recovered_written_off_chart_of_account_id',
        'interest_due_chart_of_account_id',
        'interest_paid_chart_of_account_id',
        'decimals',
        'installment_multiple_of',
        'minimum_principal',
        'default_principal',
        'maximum_principal',
        'minimum_loan_term',
        'default_loan_term',
        'maximum_loan_term',
        'repayment_frequency',
        'repayment_frequency_type',
        'minimum_interest_rate',
        'default_interest_rate',
        'maximum_interest_rate',
        'interest_rate_type',
        'enable_balloon_payments',
        'allow_schedule_adjustments',
        'grace_on_principal_paid',
        'grace_on_interest_paid',
        'grace_on_interest_charged',
        'allow_custom_grace_period',
        'allow_topup',
        'interest_methodology',
        'interest_recalculation',
        'amortization_method',
        'interest_calculation_period_type',
        'days_in_year',
        'days_in_month',
        'include_in_loan_cycle',
        'lock_guarantee_funds',
        'auto_allocate_overpayments',
        'allow_additional_charges',
        'auto_disburse',
        'repayment_account_id',
        'min_amount',
        'max_amount',
        'accounting_rule',
        'npa_overdue_days',
        'npa_suspend_accrued_income',
        'active',


    ];

    public function fund()
    {
        return $this->belongsTo(Fund::class, 'fund_id', 'id');
    }

    public function repaymentAccount()
    {
        return $this->belongsTo(Product::class, 'repayment_account_id', 'id');
    }

    public function currency(){
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
    public function loanTransactionProcessingStrategy()
    {
        return $this->belongsTo(LoanTransactionProcessingStrategy::class, 'loan_transaction_processing_strategy_id', 'id');
    }

    public function charges()
    {
        return $this->belongsToMany(LoanCharge::class, 'loan_product_linked_charges')->withPivot('id')->withTimestamps();
    }
    
    protected static function booted()
    {
        static::creating(function ($product) {
            // If product_code is not set, we'll set it after creation when we have the ID
            if (empty($product->product_code)) {
                $product->product_code = null;
            }
        });
        
        static::created(function ($product) {
            // Now that we have an ID, generate the product code if it wasn't set
            if (empty($product->product_code)) {
                // Format product code as 201, 202, 203, etc.
                // Adding 200 to the ID to start from 201
                $product->product_code = 200 + $product->id;
                $product->save();
            }
        });
    }
}

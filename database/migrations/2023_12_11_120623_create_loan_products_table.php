<?php

use App\Models\Currency;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Loan\LoanTransactionProcessingStrategy;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Currency::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanTransactionProcessingStrategy::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(ChartOfAccount::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('repayment_account_id')->constrained('products')->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('fund_source_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('loan_portfolio_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('interest_receivable_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('penalties_receivable_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('fees_receivable_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('fees_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('overpayments_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('suspended_income_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('income_from_interest_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('income_from_penalties_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('income_from_fees_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('income_from_recovery_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('losses_written_off_chart_of_account_id')->unsigned()->nullable();
            $table->bigInteger('interest_written_off_chart_of_account_id')->unsigned()->nullable();
            $table->text('name');
            $table->text('description')->nullable();
            $table->integer('decimals')->nullable();
            $table->integer('installment_multiple_of')->default(1)->nullable();
            $table->decimal('minimum_principal', 65, 0);
            $table->decimal('default_principal', 65, 0);
            $table->decimal('maximum_principal', 65, 0);
            $table->integer('minimum_loan_term');
            $table->integer('default_loan_term');
            $table->integer('maximum_loan_term');
            $table->integer('repayment_frequency');
            $table->string('repayment_frequency_type');
            $table->decimal('minimum_interest_rate', 65, 0);
            $table->decimal('default_interest_rate', 65, 0);
            $table->decimal('maximum_interest_rate', 65, 0);
            $table->string('interest_rate_type');
            $table->tinyInteger('enable_balloon_payments')->default(0);
            $table->tinyInteger('allow_schedule_adjustments')->default(0);
            $table->integer('grace_on_principal_paid')->default(0);
            $table->integer('grace_on_interest_paid')->default(0);
            $table->integer('grace_on_interest_charged')->default(0);
            $table->tinyInteger('allow_custom_grace_period')->default(0);
            $table->tinyInteger('allow_topup')->default(0);
            $table->string('interest_methodology');
            $table->tinyInteger('interest_recalculation')->default(0);
            $table->string('amortization_method')->nullable();
            $table->string('interest_calculation_period_type')->nullable();
            $table->string('days_in_year')->default('actual')->nullable();
            $table->string('days_in_month')->default('actual')->nullable();
            $table->tinyInteger('include_in_loan_cycle')->default(0);
            $table->tinyInteger('lock_guarantee_funds')->default(0);
            $table->tinyInteger('auto_allocate_overpayments')->default(0);
            $table->tinyInteger('allow_additional_charges')->default(0);
            $table->tinyInteger('auto_disburse')->default(0);
            $table->decimal('min_amount', 65, 6)->nullable();
            $table->decimal('max_amount', 65, 6)->nullable();
            $table->string('accounting_rule')->default('none')->nullable();
            $table->integer('npa_overdue_days')->default(0);
            $table->tinyInteger('npa_suspend_accrued_income')->default(0);
            $table->tinyInteger('active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};

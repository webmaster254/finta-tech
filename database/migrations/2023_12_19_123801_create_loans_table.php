<?php

use App\Models\Fund;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Currency;
use App\Models\ChartOfAccount;
use App\Models\Loan\LoanProduct;
use App\Models\Loan\LoanPurpose;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Branch::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('client_type')->default('client');
            $table->foreignIdFor(Client::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('group_id')->unsigned()->nullable();
            $table->foreignIdFor(Currency::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanProduct::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('loan_transaction_processing_strategy_id')->unsigned();
            $table->foreignIdFor(ChartOfAccount::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanPurpose::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('loan_account_number')->unique()->nullable();
            $table->foreignId('loan_officer_id')->constrained('users');
            $table->bigInteger('loan_disbursement_channel_id')->unsigned()->nullable();
            $table->date('submitted_on_date')->nullable();
            $table->foreignId('submitted_by_user_id')->constrained('users')->nullable();
            $table->date('approved_on_date')->nullable();
            $table->foreignId('approved_by_user_id')->constrained('users')->nullable();
            $table->text('approved_notes')->nullable();
            $table->date('expected_disbursement_date')->nullable();
            $table->date('expected_first_payment_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('expected_maturity_date')->nullable();
            $table->date('disbursed_on_date')->nullable();
            $table->foreignId('disbursed_by_user_id')->constrained('users')->nullable();
            $table->text('disbursed_notes')->nullable();
            $table->date('rejected_on_date')->nullable();
            $table->foreignId('rejected_by_user_id')->constrained('users')->nullable();
            $table->text('rejected_notes')->nullable();
            $table->date('written_off_on_date')->nullable();
            $table->foreignId('written_off_by_user_id')->constrained('users')->nullable();
            $table->text('written_off_notes')->nullable();
            $table->date('closed_on_date')->nullable();
            $table->foreignId('closed_by_user_id')->constrained('users')->nullable();
            $table->text('closed_notes')->nullable();
            $table->date('rescheduled_on_date')->nullable();
            $table->foreignId('rescheduled_by_user_id')->constrained('users')->nullable();
            $table->text('rescheduled_notes')->nullable();
            $table->date('withdrawn_on_date')->nullable();
            $table->foreignId('withdrawn_by_user_id')->constrained('users')->nullable();
            $table->text('withdrawn_notes')->nullable();
            $table->string('account_number')->unique()->nullable();
            $table->decimal('principal', 65, 0);
            $table->decimal('applied_amount', 65, 0)->nullable();
            $table->decimal('approved_amount', 65, 0)->nullable();
            $table->decimal('interest_rate', 65, 0);
            $table->integer('decimals')->nullable();
            $table->integer('instalment_multiple_of')->default(1)->nullable();
            $table->integer('loan_term');
            $table->integer('repayment_frequency');
            $table->string('repayment_frequency_type');
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
            $table->string('status', )->default('submitted');
            $table->decimal('disbursement_charges', 65, 0)->nullable();
            $table->decimal('principal_disbursed_derived', 65, 0)->default(0.00);
            $table->decimal('principal_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('principal_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('principal_outstanding_derived', 65, 2)->default(0.00);
            $table->decimal('interest_disbursed_derived', 65, 2)->default(0.00);
            $table->decimal('interest_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('interest_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('interest_waived_derived', 65, 2)->default(0.00);
            $table->decimal('interest_outstanding_derived', 65, 2)->default(0.00);
            $table->decimal('fees_disbursed_derived', 65, 2)->default(0.00);
            $table->decimal('fees_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('fees_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('fees_waived_derived', 65, 2)->default(0.00);
            $table->decimal('fees_outstanding_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_disbursed_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_waived_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_outstanding_derived', 65, 2)->default(0.00);
            $table->decimal('total_disbursed_derived', 65, 2)->default(0.00);
            $table->decimal('total_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('total_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('total_waived_derived', 65, 2)->default(0.00);
            $table->decimal('total_outstanding_derived', 65, 2)->default(0.00);
            $table->index('client_id');
            $table->index('loan_officer_id');
            $table->index('loan_product_id');
            $table->index('branch_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};

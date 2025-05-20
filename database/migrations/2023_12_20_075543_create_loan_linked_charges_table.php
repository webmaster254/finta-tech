<?php

use App\Models\Loan\Loan;
use App\Models\Loan\LoanCharge;
use App\Models\Loan\LoanChargeType;
use App\Models\Loan\LoanTransaction;
use App\Models\Loan\LoanChargeOption;
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
        Schema::create('loan_linked_charges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Loan::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanCharge::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanChargeType::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanChargeOption::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanTransaction::class)->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->text('name')->nullable();
            $table->decimal('amount', 65, 2);
            $table->decimal('calculated_amount', 65, 2)->nullable();
            $table->bigInteger('client_id');
            $table->decimal('amount_paid_derived', 65, 2)->nullable();
            $table->decimal('amount_waived_derived', 65, 2)->nullable();
            $table->decimal('amount_written_off_derived', 65, 2)->nullable();
            $table->decimal('amount_outstanding_derived', 65, 2)->nullable();
            $table->tinyInteger('is_penalty')->default(0);
            $table->tinyInteger('waived')->default(0);
            $table->tinyInteger('is_paid')->default(0);
            $table->index('loan_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_linked_charges');
    }
};

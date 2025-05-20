<?php

use App\Models\Loan\Loan;
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
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Loan::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->date('paid_by_date')->nullable();
            $table->date('from_date')->nullable();
            $table->date('due_date');
            $table->integer('installment')->nullable();
            $table->decimal('principal', 65, 2)->default(0.00);
            $table->decimal('principal_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('principal_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('interest', 65, 2)->default(0.00);
            $table->decimal('interest_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('interest_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('interest_waived_derived', 65, 2)->default(0.00);
            $table->decimal('fees', 65, 2)->default(0.00);
            $table->decimal('fees_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('fees_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('fees_waived_derived', 65, 2)->default(0.00);
            $table->decimal('penalties', 65, 2)->default(0.00);
            $table->decimal('penalties_repaid_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_written_off_derived', 65, 2)->default(0.00);
            $table->decimal('penalties_waived_derived', 65, 2)->default(0.00);
            $table->decimal('total_due', 65, 2)->default(0.00);
            $table->decimal('payoff', 65, 2)->default(0.00);
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->timestamps();
            $table->index('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayment_schedules');
    }
};

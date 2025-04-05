<?php

use App\Models\Currency;
use App\Models\Loan\LoanChargeType;
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
        Schema::create('loan_charges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Currency::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanChargeType::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanChargeOption::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->text('name');
            $table->decimal('amount', 65, 6);
            $table->tinyInteger('schedule')->default(0)->nullable();
            $table->integer('schedule_frequency')->nullable();
            $table->string('schedule_frequency_type')->nullable();
            $table->tinyInteger('is_penalty')->default(0)->nullable();
            $table->tinyInteger('active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_charges');
    }
};

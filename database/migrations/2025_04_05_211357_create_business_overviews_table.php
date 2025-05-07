<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_overviews', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->decimal('current_stock', 10, 2)->nullable();
            $table->decimal('operating_capital', 10, 2)->nullable();
            $table->decimal('average_weekly_sales', 10, 2)->nullable();
            $table->decimal('average_weekly_purchase', 10, 2)->nullable();
            $table->decimal('average_weekly_stock_balance', 10, 2)->nullable();
            $table->decimal('cost_of_sales', 10, 2)->nullable();
            $table->decimal('house_rent', 10, 2)->nullable();
            $table->decimal('hs_electricity', 10, 2)->nullable();
            $table->decimal('hs_food', 10, 2)->nullable();
            $table->decimal('hs_transport', 10, 2)->nullable();
            $table->decimal('clothings', 10, 2)->nullable();
            $table->decimal('school_fees', 10, 2)->nullable();
            $table->decimal('hs_total', 10, 2)->nullable();
            $table->decimal('bs_rent', 10, 2)->nullable();
            $table->decimal('bs_electricity', 10, 2)->nullable();
            $table->decimal('bs_license', 10, 2)->nullable();
            $table->decimal('bs_transport', 10, 2)->nullable();
            $table->decimal('bs_wages', 10, 2)->nullable();
            $table->decimal('bs_contributions', 10, 2)->nullable();
            $table->decimal('bs_loan_repayment', 10, 2)->nullable();
            $table->decimal('bs_other_drawings', 10, 2)->nullable();
            $table->decimal('bs_spoilts_goods', 10, 2)->nullable();
            $table->decimal('owner_salary', 10, 2)->nullable();
            $table->decimal('bs_total', 10, 2)->nullable();
            $table->decimal('gross_profit', 10, 2)->nullable();
            $table->decimal('net_profit', 10, 2)->nullable();
            $table->decimal('affordability', 10, 2)->nullable();
            $table->string('mpesa_statement')->nullable();
            $table->string('mpesa_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_overviews');
    }
};

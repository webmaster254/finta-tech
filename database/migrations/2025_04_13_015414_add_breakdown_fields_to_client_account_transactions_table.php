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
        Schema::table('client_account_transactions', function (Blueprint $table) {
            // Fields for tracking loan repayment breakdown
            $table->decimal('principal_amount', 15, 2)->nullable();
            $table->decimal('interest_amount', 15, 2)->nullable();
            $table->decimal('fees_amount', 15, 2)->nullable();
            $table->decimal('penalties_amount', 15, 2)->nullable();
            $table->json('repayment_schedule_breakdown')->nullable(); // For storing detailed breakdown by schedule
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_account_transactions', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn([
                'principal_amount',
                'interest_amount',
                'fees_amount',
                'penalties_amount',
                'repayment_schedule_breakdown'
            ]);
        });
    }
};

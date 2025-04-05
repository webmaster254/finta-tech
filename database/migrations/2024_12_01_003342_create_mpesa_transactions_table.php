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
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->dateTime('transaction_date');
            $table->string('msisdn');
            $table->string('sender');
            $table->string('transaction_type');
            $table->string('bill_reference');
            $table->decimal('amount', 10, 2);
            $table->string('organization_name');
            $table->string('response_ref_id');
            $table->string('response_code');
            $table->string('response_message');
            $table->string('status')->default('not_resolved');
            $table->json('raw_response')->nullable();
            $table->timestamps();

            // Index frequently queried columns
            $table->index('transaction_date');
            $table->index('msisdn');
            $table->index('status');
            $table->index('bill_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};

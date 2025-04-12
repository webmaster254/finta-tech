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
        Schema::create('client_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->string('transaction_type'); // deposit, withdrawal, loan_disbursement, loan_repayment, fee, interest, etc.
            $table->enum('transaction_direction', ['debit', 'credit'])->default('credit'); // debit (money out), credit (money in)
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->string('currency_code')->default('KES');
            $table->string('payment_method')->nullable(); // cash, mpesa, bank_transfer, etc.
            $table->string('reference_number')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('posted_by')->nullable();
            $table->string('status')->default('completed'); // pending, completed, failed, reversed
            $table->json('metadata')->nullable(); // For additional transaction details
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_account_transactions');
    }
};

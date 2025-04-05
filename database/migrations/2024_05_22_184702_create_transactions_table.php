<?php

use App\Models\Branch;
use App\Models\ChartOfAccount;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Branch::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('loan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(ChartOfAccount::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // deposit, withdrawal, journal
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();
            $table->bigInteger('amount')->default(0);
            $table->boolean('pending')->default(false);
            $table->boolean('reviewed')->default(false);
            $table->dateTime('posted_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

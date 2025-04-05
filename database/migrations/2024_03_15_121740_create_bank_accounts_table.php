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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_of_account_id')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('bank_holder_name')->nullable();
            $table->string('name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('type')->nullable();
            $table->string('mobile')->nullable();
            $table->decimal('balance', 65, 2)->nullable();
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('SET NULL');
            $table->decimal('opening_balance', 65, 2)->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};

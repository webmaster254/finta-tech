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
        Schema::create('asset_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->integer('chart_of_account_fixed_asset_id')->nullable();
            $table->integer('chart_of_account_asset_id')->nullable();
            $table->integer('chart_of_account_contra_asset_id')->nullable();
            $table->integer('chart_of_account_expense_id')->nullable();
            $table->integer('chart_of_account_liability_id')->nullable();
            $table->integer('chart_of_account_income_id')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_types');
    }
};

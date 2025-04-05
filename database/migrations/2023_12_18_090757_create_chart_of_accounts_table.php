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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('parent_id')->nullable();
            $table->text('name')->nullable();
            $table->integer('gl_code')->nullable();
            $table->string('account_type')->default('asset');
            $table->string('currency_code')->nullable();
            $table->boolean('default')->default(false);
            $table->unsignedBigInteger('accountable_id')->nullable();
            $table->string('accountable_type')->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('allow_manual')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};

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
        Schema::create('employment_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('employer_name');
            $table->string('employment_type');
            $table->string('occupation');
            $table->string('designation');
            $table->date('working_since');
            $table->decimal('gross_income', 10, 2)->default(0.00);
            $table->decimal('other_income', 10, 2)->default(0.00);
            $table->decimal('expense', 10, 2)->default(0.00);
            $table->decimal('net_income', 10, 2)->default(0.00);
            $table->string('employment_letter')->nullable();
            $table->string('pay_slip')->nullable();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_infos');
    }
};

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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('business_type')->nullable();
            $table->text('description')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('ownership')->nullable();
            $table->string('premise_ownership')->nullable();
            $table->string('employees')->nullable();
            $table->string('sector')->nullable();
            $table->string('major_products')->nullable();
            $table->string('major_suppliers')->nullable();
            $table->string('major_customers')->nullable();
            $table->string('major_competitors')->nullable();
            $table->string('strengths')->nullable();
            $table->string('weaknesses')->nullable();
            $table->string('opportunities')->nullable();
            $table->string('threats')->nullable();
            $table->string('mitigations')->nullable();
            $table->string('insurance')->nullable();
            $table->string('trading_license')->nullable();
            $table->string('business_permit')->nullable();
            $table->string('certificate_of_incorporation')->nullable();
            $table->string('health_certificate')->nullable();
            $table->date('establishment_date')->nullable();
            $table->string('record_maintained')->nullable();
            $table->string('assessed_by')->nullable();
            $table->date('assessed_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};

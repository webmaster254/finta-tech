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
        Schema::create('investment_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('investment_id');
            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->foreign('investment_id')->references('id')->on('investors')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_payment_schedules');
    }
};

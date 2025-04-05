<?php

use App\Models\Loan\LoanCharge;
use App\Models\Loan\LoanProduct;
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
        Schema::create('loan_product_linked_charges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(LoanProduct::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanCharge::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_product_linked_charges');
    }
};

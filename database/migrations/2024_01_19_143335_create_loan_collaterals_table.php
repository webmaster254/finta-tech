<?php

use App\Models\Loan\Loan;
use Illuminate\Support\Facades\Schema;
use App\Models\Loan\LoanCollateralType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_collaterals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Loan::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(LoanCollateralType::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('value', 65, 0)->nullable();
            $table->decimal('forced_value', 65, 0)->nullable();
            $table->string('file')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_collaterals');
    }
};

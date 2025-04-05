<?php

use App\Models\Branch;
use App\Models\Currency;
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
        Schema::create('expenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Branch::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('expense_chart_of_account_id')->nullable();
            $table->foreignIdFor(Currency::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('asset_chart_of_account_id')->nullable();
            $table->decimal('amount',65,2)->default(0.00);
            $table->date('date')->nullable();
            $table->tinyInteger('recurring')->default(0);
            $table->string('recur_frequency')->default(31);
            $table->date('recur_start_date')->nullable();
            $table->date('recur_end_date')->nullable();
            $table->date('recur_next_date')->nullable();
            $table->string('recur_type')->default('month');
            $table->text('notes')->nullable();
            $table->text('description')->nullable();
            $table->text('files')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

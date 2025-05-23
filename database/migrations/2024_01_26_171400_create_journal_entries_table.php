<?php

use App\Models\Branch;
use App\Models\Currency;
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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->string('transaction_number')->nullable();
            $table->bigInteger('payment_detail_id')->unsigned()->nullable();
            $table->foreignIdFor(Currency::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(Branch::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(ChartOfAccount::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('transaction_type')->nullable();
            $table->string('transaction_sub_type')->nullable();
            $table->text('name')->nullable();
            $table->date('date')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('reference')->nullable();
            $table->integer('client_id')->nullable();
            $table->decimal('debit', 65, 0)->nullable();
            $table->decimal('credit', 65, 0)->nullable();
            $table->decimal('balance', 65, 0)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->tinyInteger('reversed')->default(0);
            $table->tinyInteger('reversible')->default(1);
            $table->tinyInteger('manual_entry')->default(0);
            $table->string('receipt')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('chart_of_account_id', 'chart_of_account_id_index');
            $table->index('currency_id', 'currency_id_index');
            $table->index('created_by_id', 'created_by_id_index');
            $table->index('client_id', 'client_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};

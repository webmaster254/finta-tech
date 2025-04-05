<?php

use App\Models\Loan\Loan;
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
        Schema::create('loan_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Loan::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('size')->nullable();
            $table->text('file');
            $table->timestamps();
            $table->index('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_files');
    }
};

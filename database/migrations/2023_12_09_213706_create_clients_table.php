<?php

use App\Models\Title;
use App\Models\Branch;
use App\Models\ClientType;
use App\Models\Profession;
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
        Schema::create('clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Title::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('loan_officer_id')->constrained('users');
            $table->foreignIdFor(Branch::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('account_number')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('status')->default('pending');
            $table->foreignIdFor(Profession::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(ClientType::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('pin_url')->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
          $table->integer('suggested_loan_limit')->nullable();
            $table->date('created_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

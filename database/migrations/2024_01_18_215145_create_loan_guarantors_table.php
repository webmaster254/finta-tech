<?php

use App\Models\Title;
use App\Models\Client;
use App\Models\Loan\Loan;
use App\Models\Profession;
use App\Models\ClientRelationship;
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
        Schema::create('loan_guarantors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Loan::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('is_client')->default(0);
            $table->foreignIdFor(Client::class)->nullable()->constrained()->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->default('unspecified')->nullable();
            $table->string('status')->default('pending');
            $table->string('marital_status')->default('unspecified')->nullable();
            $table->bigInteger('country_id')->unsigned()->nullable();
            $table->bigInteger('id_number')->unsigned()->nullable();
            $table->foreignIdFor(Title::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(Profession::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(ClientRelationship::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('mobile')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('employer')->nullable();
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->date('created_date')->nullable();
            $table->date('joined_date')->nullable();
            $table->decimal('guaranteed_amount', 65, 0)->nullable();
            $table->timestamps();
            $table->index('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_guarantors');
    }
};

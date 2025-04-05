<?php

use App\Models\Client;
use App\Models\ClientRelationship;
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
        Schema::create('client_next_of_kins', function (Blueprint $table) {$table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignIdFor(Client::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(ClientRelationship::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->bigInteger('country_id')->unsigned()->nullable();
            $table->foreignIdFor(Profession::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });


        Schema::create('client-clients_kins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(\App\Models\Client::class);
            $table->foreignIdFor(\App\Models\ClientNextOfKins::class);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_next_of_kins');
        Schema::dropIfExists('client-clients_kins');
    }
};

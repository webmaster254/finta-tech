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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('address_type')->nullable();
            $table->string('country')->nullable();
            $table->foreignId('county_id')->constrained('county')->nullable();
            $table->foreignId('sub_county_id')->constrained('subcounty')->nullable();
            $table->foreignId('ward_id')->constrained('towns')->nullable();
            $table->string('village')->nullable();
            $table->string('street')->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('building')->nullable();
            $table->string('floor_no')->nullable();
            $table->string('house_no')->nullable();
            $table->string('estate')->nullable();
            $table->unsignedBigInteger('image')->nullable();
            $table->string('image_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

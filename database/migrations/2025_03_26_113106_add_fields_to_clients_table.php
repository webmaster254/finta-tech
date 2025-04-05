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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('aka')->nullable()->comment('Also known as');
            $table->string('education_level')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('other_mobile_no')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('type_of_tech')->nullable();
            $table->boolean('is_published')->default(false);
            $table->json('addresses')->nullable();
            $table->string('spouce_id')->nullable();
            $table->string('spouce_name')->nullable();
            $table->string('spouce_mobile')->nullable();
            $table->string('spouce_occupation')->nullable();
            $table->json('next_of_kins')->nullable();
            $table->string('source_of_income')->nullable();
            $table->string('id_front')->nullable();
            $table->string('id_back')->nullable();
            $table->string('hashed_mobile')->nullable();
            $table->string('signature')->nullable();
            $table->string('consent_form')->nullable();
            $table->json('referees')->nullable();
        });
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'aka',
                'education_level',
                'id_type',
                'id_number',
                'other_mobile_no',
                'kra_pin',
                'postal_code',
                'type_of_tech',
                'is_published',
                'addresses',
                'spouce_id',    
                'spouce_name',  
                'spouce_mobile',
                'spouce_occupation',
                'next_of_kins',
                'source_of_income',
                'id_front',
                'id_back',
                'hashed_mobile',
                'signature',
                'consent_form',
                'referees',
            ]);
        });
    }
};

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
        Schema::table('investors', function (Blueprint $table) {
            $table->string('avatar')->nullable();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->bigInteger('rejected_by_id')->unsigned()->nullable();
            $table->bigInteger('invested_by_id')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn('avatar');
            $table->dropColumn('approved_by_id');
            $table->dropColumn('rejected_by_id');
            $table->dropColumn('invested_by_id');
        });
    }
};

<?php

use App\Models\Branch;
use App\Models\AssetType;
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
        Schema::create('assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('created_by_id')->constrained('users');
             $table->foreignIdFor(AssetType::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(Branch::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 65, 2)->nullable();
            $table->decimal('replacement_value', 65, 2)->nullable();
            $table->decimal('value', 65, 2)->nullable();
            $table->integer('life_span')->nullable();
            $table->decimal('salvage_value', 65, 2)->nullable();
            $table->text('serial_number')->nullable();
            $table->string('bought_from')->nullable();
            $table->string('purchase_year')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->nullable();
            $table->tinyInteger('active')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

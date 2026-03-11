<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['silase', 'cf_jember', 'jagung_halus', 'konsentrat']);
            $table->decimal('current_stock', 8, 2)->default(0);
            $table->decimal('price_per_kg', 10, 2)->nullable();
            $table->string('unit')->default('kg');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};

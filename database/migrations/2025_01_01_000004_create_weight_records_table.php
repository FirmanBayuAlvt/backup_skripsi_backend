<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weight_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 5, 2);
            $table->date('record_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_records');
    }
};

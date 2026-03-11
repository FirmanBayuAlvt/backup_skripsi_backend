<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('prediction_days');
            $table->decimal('predicted_gain', 5, 3);
            $table->decimal('confidence', 3, 2)->nullable();
            $table->decimal('interval_lower', 5, 3)->nullable();
            $table->decimal('interval_upper', 5, 3)->nullable();
            $table->json('recommendations')->nullable();
            $table->json('input_features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};

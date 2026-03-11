<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestocks', function (Blueprint $table) {
            $table->id();
            $table->string('ear_tag')->unique();
            $table->enum('breed_type', ['domba_lokal', 'domba_ekor_gemuk', 'domba_garut']);
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date');
            $table->decimal('initial_weight', 5, 2);
            $table->enum('health_status', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestocks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_availability', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->tinyInteger('day_of_week');

            $table->time('available_from');
            $table->time('available_to');

            $table->timestamps();

            $table->index(['teacher_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_availability');
    }
};
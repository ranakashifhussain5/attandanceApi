<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_generation_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hod_id')
                ->constrained('users');

            $table->foreignId('department_id')
                ->constrained();

            $table->integer('semester');

            $table->enum('status', [
                'pending',
                'processing',
                'success',
                'failed'
            ])->default('pending');

            $table->integer('schedules_created')->default(0);

            $table->integer('conflicts_detected')->default(0);

            $table->json('conflict_details')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('generated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_generation_logs');
    }
};
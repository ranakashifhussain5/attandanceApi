<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('users');

            $table->date('session_date');

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->enum('status', [
                'pending',
                'open',
                'closed'
            ])->default('pending');

            $table->integer('total_present')->default(0);
            $table->integer('total_students')->default(0);

            $table->timestamps();

            $table->unique(['schedule_id', 'session_date']);

            $table->index([
                'status',
                'teacher_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
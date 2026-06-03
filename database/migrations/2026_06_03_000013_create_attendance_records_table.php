<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', [
                'present',
                'absent',
                'late',
                'excused'
            ])->default('present');

            $table->string('wifi_bssid_captured')->nullable();

            $table->decimal('latitude_captured', 10, 8)->nullable();
            $table->decimal('longitude_captured', 11, 8)->nullable();

            $table->boolean('wifi_verified')->default(false);
            $table->boolean('gps_verified')->default(false);

            $table->string('ip_address')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamp('marked_at')->nullable();

            $table->timestamps();

            $table->unique([
                'attendance_session_id',
                'student_id'
            ]);

            $table->index([
                'student_id',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('enrolled_at');

            $table->enum('status', [
                'active',
                'dropped',
                'graduated'
            ])->default('active');

            $table->timestamps();

            $table->unique(['batch_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_students');
    }
};
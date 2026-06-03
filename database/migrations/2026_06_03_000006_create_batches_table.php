<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->integer('semester')->default(1);
            $table->integer('start_year');
            $table->integer('end_year');
            $table->integer('max_students')->default(40);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['program_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
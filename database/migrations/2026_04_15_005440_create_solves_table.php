<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('puzzle_type', 20)->default('333');
            $table->text('scramble');
            $table->unsignedInteger('time_ms');
            $table->boolean('dnf')->default(false);
            $table->boolean('plus2')->default(false);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'puzzle_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solves');
    }
};

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
        Schema::create('duel_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('puzzle_type', 20)->default('333');
            $table->string('scramble_seed', 64);
            $table->text('scramble_text');
            $table->enum('status', ['waiting', 'in_progress', 'finished'])->default('waiting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duel_rooms');
    }
};

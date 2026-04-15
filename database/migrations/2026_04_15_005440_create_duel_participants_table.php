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
        Schema::create('duel_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duel_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('time_ms')->nullable();
            $table->boolean('dnf')->default(false);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['duel_room_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duel_participants');
    }
};

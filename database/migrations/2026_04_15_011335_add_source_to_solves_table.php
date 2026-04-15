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
        Schema::table('solves', function (Blueprint $table) {
            $table->string('source', 10)->default('solo')->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('solves', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};

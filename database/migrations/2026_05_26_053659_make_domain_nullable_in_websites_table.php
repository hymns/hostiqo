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
        Schema::table('websites', function (Blueprint $table) {
            // Drop unique constraint first
            $table->dropUnique(['domain']);
            
            // Make domain nullable
            $table->string('domain')->nullable()->change();
            
            // Re-add unique constraint but allow multiple NULLs (handled by validation)
            // Note: MySQL allows multiple NULL values in unique columns
            $table->unique('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->string('domain')->nullable(false)->change();
        });
    }
};

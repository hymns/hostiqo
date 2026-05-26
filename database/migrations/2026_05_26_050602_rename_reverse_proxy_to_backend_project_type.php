<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add 'backend' to enum first (keep 'reverse-proxy' temporarily)
        DB::statement("ALTER TABLE websites MODIFY COLUMN project_type ENUM('php', 'static', 'reverse-proxy', 'backend') DEFAULT 'php'");

        // Step 2: Update existing 'reverse-proxy' records to 'backend'
        DB::table('websites')
            ->where('project_type', 'reverse-proxy')
            ->update(['project_type' => 'backend']);

        // Step 3: Remove 'reverse-proxy' from enum
        DB::statement("ALTER TABLE websites MODIFY COLUMN project_type ENUM('php', 'static', 'backend') DEFAULT 'php'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Revert project_type enum
        DB::statement("ALTER TABLE websites MODIFY COLUMN project_type ENUM('php', 'static', 'reverse-proxy') DEFAULT 'php'");

        // Step 2: Update 'backend' back to 'reverse-proxy'
        DB::table('websites')
            ->where('project_type', 'backend')
            ->update(['project_type' => 'reverse-proxy']);
    }
};

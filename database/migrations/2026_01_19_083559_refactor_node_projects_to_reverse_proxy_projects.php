<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Rename node_version column to runtime
        Schema::table('websites', function (Blueprint $table) {
            $table->renameColumn('node_version', 'runtime');
        });

        // Step 2: Update existing 'node' project_type to 'reverse-proxy'
        DB::table('websites')
            ->where('project_type', 'node')
            ->update([
                'project_type' => 'reverse-proxy',
                'runtime' => DB::raw("COALESCE(runtime, 'Node.js')")
            ]);

        // Step 3: Modify project_type enum to include 'reverse-proxy' and remove 'node'
        // Note: Laravel doesn't support modifying enums directly, so we need raw SQL
        DB::statement("ALTER TABLE websites MODIFY COLUMN project_type ENUM('php', 'static', 'reverse-proxy') DEFAULT 'php'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Revert project_type enum
        DB::statement("ALTER TABLE websites MODIFY COLUMN project_type ENUM('php', 'node', 'static') DEFAULT 'php'");

        // Step 2: Update 'reverse-proxy' back to 'node'
        DB::table('websites')
            ->where('project_type', 'reverse-proxy')
            ->update(['project_type' => 'node']);

        // Step 3: Rename runtime back to node_version
        Schema::table('websites', function (Blueprint $table) {
            $table->renameColumn('runtime', 'node_version');
        });
    }
};

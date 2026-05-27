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
        // Add 'skipped' to nginx_status ENUM
        // This is for backend projects without domain that don't need nginx config
        DB::statement("ALTER TABLE websites MODIFY COLUMN nginx_status ENUM('pending', 'active', 'failed', 'inactive', 'skipped') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'skipped' from nginx_status ENUM
        DB::statement("ALTER TABLE websites MODIFY COLUMN nginx_status ENUM('pending', 'active', 'failed', 'inactive') DEFAULT 'pending'");
    }
};

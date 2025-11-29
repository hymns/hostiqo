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
        Schema::table('system_metrics', function (Blueprint $table) {
            $table->integer('db_connections')->nullable()->after('network_tx_bytes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_metrics', function (Blueprint $table) {
            $table->dropColumn('db_connections');
        });
    }
};

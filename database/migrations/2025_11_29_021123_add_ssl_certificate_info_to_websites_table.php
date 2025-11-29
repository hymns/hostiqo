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
            $table->string('ssl_issuer')->nullable()->after('ssl_status');
            $table->timestamp('ssl_issued_at')->nullable()->after('ssl_issuer');
            $table->timestamp('ssl_expires_at')->nullable()->after('ssl_issued_at');
            $table->timestamp('ssl_last_checked_at')->nullable()->after('ssl_expires_at');
            $table->boolean('ssl_auto_renew')->default(true)->after('ssl_last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'ssl_issuer',
                'ssl_issued_at',
                'ssl_expires_at',
                'ssl_last_checked_at',
                'ssl_auto_renew',
            ]);
        });
    }
};

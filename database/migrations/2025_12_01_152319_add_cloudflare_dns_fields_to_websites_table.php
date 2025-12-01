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
            $table->string('cloudflare_zone_id')->nullable()->after('domain');
            $table->string('cloudflare_record_id')->nullable()->after('cloudflare_zone_id');
            $table->string('server_ip')->nullable()->after('cloudflare_record_id');
            $table->enum('dns_status', ['pending', 'active', 'failed', 'none'])->default('none')->after('server_ip');
            $table->text('dns_error')->nullable()->after('dns_status');
            $table->timestamp('dns_last_synced_at')->nullable()->after('dns_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'cloudflare_zone_id',
                'cloudflare_record_id',
                'server_ip',
                'dns_status',
                'dns_error',
                'dns_last_synced_at',
            ]);
        });
    }
};

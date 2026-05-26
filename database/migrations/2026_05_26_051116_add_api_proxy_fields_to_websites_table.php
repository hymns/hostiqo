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
            $table->boolean('enable_api_proxy')->default(false)->after('port');
            $table->string('api_proxy_path')->nullable()->after('enable_api_proxy');
            $table->integer('api_proxy_port')->nullable()->after('api_proxy_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn(['enable_api_proxy', 'api_proxy_path', 'api_proxy_port']);
        });
    }
};

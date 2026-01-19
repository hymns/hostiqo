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
        Schema::create('systemd_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->text('exec_start');
            $table->string('working_directory');
            $table->string('user')->default('www-data');
            $table->enum('type', ['simple', 'forking', 'oneshot', 'notify'])->default('simple');
            $table->enum('restart', ['no', 'always', 'on-failure', 'on-abnormal'])->default('always');
            $table->integer('restart_sec')->default(10);
            $table->text('environment')->nullable();
            $table->string('standard_output')->default('journal');
            $table->string('standard_error')->default('journal');
            $table->boolean('is_active')->default(true);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('systemd_services');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Sleep mode: stop a server nobody is on and wake it when a player connects. Both columns are
     * NULL while the server follows the panel-wide default (Admin -> Settings -> Advanced).
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('sleep_enabled')->nullable()->after('auto_backup_last_at');
            $table->unsignedSmallInteger('sleep_minutes')->nullable()->after('sleep_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['sleep_enabled', 'sleep_minutes']);
        });
    }
};

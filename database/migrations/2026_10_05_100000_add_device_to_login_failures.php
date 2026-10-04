<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Failed logins are also counted per browser ("d:" + a hash of the device cookie), so a user who
        // switches IP address is still recognised, without ever blocking a shared address.
        Schema::table('login_failures', function (Blueprint $table) {
            $table->string('device', 45)->nullable()->after('ip');
            $table->index(['device', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('login_failures', function (Blueprint $table) {
            $table->dropIndex(['device', 'created_at']);
            $table->dropColumn('device');
        });
    }
};

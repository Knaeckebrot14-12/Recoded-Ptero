<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * "Move them back": a drain of mode "back" returns the servers an earlier drain ("away") moved
 * off the node, to the node they came from.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('node_drains', function (Blueprint $table) {
            $table->string('mode', 8)->default('away')->after('status');
            $table->unsignedBigInteger('source_drain_id')->nullable()->after('mode');

            $table->foreign('source_drain_id')->references('id')->on('node_drains')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('node_drains', function (Blueprint $table) {
            $table->dropForeign(['source_drain_id']);
            $table->dropColumn(['mode', 'source_drain_id']);
        });
    }
};

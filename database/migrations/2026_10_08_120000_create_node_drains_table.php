<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * "Move all servers away" on the admin node page: one drain per run and one row per server on the
 * node with how far it got. The servers themselves move with the normal server transfers.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('node_drains', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('node_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('status', 16);
            $table->boolean('maintenance_before')->default(false);
            $table->timestamps();
            $table->timestamp('finished_at')->nullable();

            $table->index(['node_id', 'status']);
            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('node_drain_servers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('node_drain_id');
            $table->unsignedInteger('server_id');
            $table->string('status', 16);
            $table->unsignedInteger('target_node_id')->nullable();
            $table->unsignedInteger('server_transfer_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['node_drain_id', 'status']);
            $table->foreign('node_drain_id')->references('id')->on('node_drains')->cascadeOnDelete();
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreign('target_node_id')->references('id')->on('nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_drain_servers');
        Schema::dropIfExists('node_drains');
    }
};

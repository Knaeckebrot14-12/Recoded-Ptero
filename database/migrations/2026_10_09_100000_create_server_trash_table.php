<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Files deleted in the file manager, kept for a while in the server's .trash folder. A "clone"
     * row is the content a server had before the files of another server were copied into it.
     */
    public function up(): void
    {
        Schema::create('server_trash', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('kind', 16)->default('file');
            $table->string('batch', 64);
            $table->string('original_path', 1024);
            $table->string('trash_path', 1024);
            $table->boolean('is_file')->default(true);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('label')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['server_id', 'created_at']);
            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_trash');
    }
};

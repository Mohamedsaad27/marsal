<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('message_id')->primary();
            $table->foreignUuid('conversation_id')
                  ->references('conversation_id')->on('conversations')->onDelete('cascade');
            $table->foreignUuid('sender_id')
                  ->references('user_id')->on('users')->onDelete('cascade');
            $table->text('body')->nullable()
                  ->comment('Text body; NULL when the message is an attachment only');
            $table->unsignedTinyInteger('message_type')->default(1)
                  ->comment('1=text|2=image|3=voice — MessageTypeEnum');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('conversation_id');
            $table->index('sender_id');
        });

        // Per-user read receipts
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('message_id')
                  ->references('message_id')->on('messages')->onDelete('cascade');
            $table->foreignUuid('user_id')
                  ->references('user_id')->on('users')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['message_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('messages');
    }
};

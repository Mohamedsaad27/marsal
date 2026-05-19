<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('conversation_id')->primary();
            $table->foreignUuid('order_id')->nullable()
                  ->references('order_id')->on('orders')->onDelete('set null')
                  ->comment('NULL = general (not tied to a specific order)');
            $table->tinyInteger('conversation_type')->default(1)
                  ->comment('1=admin_agent|2=admin_company|3=admin_agent_company — ConversationTypeEnum');
            $table->softDeletes();
            $table->timestamps();

            $table->index('order_id');
            $table->index('conversation_type');
        });

        // Pivot: which users participate in each conversation
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')
                  ->references('conversation_id')->on('conversations')->onDelete('cascade');
            $table->foreignUuid('user_id')
                  ->references('user_id')->on('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};

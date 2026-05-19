<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postponed_schedules', function (Blueprint $table) {
            $table->uuid('postponed_schedule_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->foreignUuid('delivery_agent_id')->nullable()->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
            $table->date('scheduled_date')->comment('New delivery date chosen by agent');
            $table->text('reason')->nullable();
            $table->boolean('reminder_sent')->default(false)->comment('1 = FCM reminder already dispatched');
            $table->boolean('is_reassigned')->default(false)->comment('1 = order was moved to a different agent');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'delivery_agent_id', 'scheduled_date', 'reminder_sent', 'is_reassigned'], 'idx_ps_order_agent_date_reminder_reassigned');
            $table->index('order_id', 'idx_ps_order');
            $table->index('delivery_agent_id', 'idx_ps_agent');
            $table->index('scheduled_date', 'idx_ps_date');
            $table->index('reminder_sent', 'idx_ps_reminder');
            $table->index('is_reassigned', 'idx_ps_reassigned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postponed_schedules');
    }
};

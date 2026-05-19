<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refusal_timer_logs', function (Blueprint $table) {
            $table->uuid('refusal_timer_log_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->foreignUuid('delivery_agent_id')->nullable()->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
            $table->tinyInteger('resolution')->nullable()->comment('1=delivered|2=refused_paid|3=refused_no_pay|4=expired — RefusalResolutionEnum');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('started_at + system_settings.refusal_timer_minutes');
            $table->timestamp('resolved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'resolution']);
            $table->index(['delivery_agent_id', 'resolution']);
            $table->index('order_id');
            $table->index('delivery_agent_id');
            $table->index('resolution');
            $table->index('expires_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refusal_timer_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->uuid('order_status_history_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->tinyInteger('from_status_id')->nullable()
                  ->comment('NULL only for the very first entry (creation)');
            $table->tinyInteger('to_status_id');
            $table->foreignUuid('changed_by')->nullable()->references('user_id')->on('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'from_status_id', 'to_status_id', 'changed_by'], 'idx_osh_order_status_changed');
            $table->index('order_id', 'idx_osh_order');
            $table->index('from_status_id', 'idx_osh_from_status');
            $table->index('to_status_id', 'idx_osh_to_status');
            $table->index('changed_by', 'idx_osh_changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};

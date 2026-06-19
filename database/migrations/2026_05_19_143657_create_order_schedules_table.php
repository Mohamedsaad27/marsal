<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_schedules', function (Blueprint $table) {
            $table->uuid('order_schedule_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->cascadeOnDelete();
            $table->date('expected_delivery_date')->nullable();
            $table->date('postponed_date')->nullable()->comment('Filled by agent when status = postponed');
            $table->text('schedule_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'expected_delivery_date', 'postponed_date'], 'idx_os_order_date_postponed');
            $table->index('expected_delivery_date', 'idx_os_expected_date');
            $table->index('postponed_date', 'idx_os_postponed_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_schedules');
    }
};

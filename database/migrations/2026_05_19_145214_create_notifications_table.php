<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('notification_id')->primary();
            $table->foreignUuid('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->tinyInteger('notification_type')->comment('1=new_order|2=status_change|3=approval_request|4=timer_start|5=timer_expired|6=new_message|7=phone_updated|8=postponed_reminder — NotificationTypeEnum');
            $table->string('title_ar', 255)->nullable();
            $table->text('body_ar')->nullable();
            $table->json('data')->nullable()
                  ->comment('Arbitrary payload e.g. {"order_id":123}');
            $table->tinyInteger('is_read')->default(0);
            $table->boolean('sent_via_fcm')->default(false);
            $table->string('fcm_message_id', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index('user_id');
            $table->index('notification_type');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

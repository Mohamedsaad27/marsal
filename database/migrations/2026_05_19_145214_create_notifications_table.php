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
            $table->foreignUuid('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->unsignedTinyInteger('notification_type')
                  ->comment('1=new_order|2=status_change|3=approval_request|4=timer_start|5=timer_expired|6=new_message|7=phone_updated|8=postponed_reminder — NotificationTypeEnum');

            $table->string('title_ar', 255)->nullable();
            $table->text('body_ar')->nullable();

            $table->json('data')->nullable()
                  ->comment('Arbitrary payload e.g. {"order_id":"uuid-here"}');

            $table->unsignedTinyInteger('is_read')
                  ->default(0)
                  ->comment('0=unread|1=read');

            $table->timestamp('read_at')->nullable()
                  ->comment('Timestamp of when the notification was first read');

            $table->unsignedTinyInteger('sent_via_fcm')
                  ->default(0)
                  ->comment('0=not_sent|1=sent');

            $table->string('fcm_message_id', 255)->nullable();

            $table->timestamps();

            // Composite index covers both user-scoped list and unread-count queries
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
            $table->index(['user_id', 'created_at'], 'idx_notifications_user_created');
            $table->index('notification_type', 'idx_notifications_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

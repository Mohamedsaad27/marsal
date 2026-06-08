<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36)->nullable()->index('idx_al_user');
            $table->unsignedTinyInteger('actor_type')
                ->default(1)
                ->comment('1=super_admin|2=shipping_company|3=delivery_agent|4=system');
            $table->unsignedTinyInteger('event')
                ->comment('1=created|2=updated|3=deleted|4=restored|5=login|6=logout|7=status_changed|8=assigned|9=approved|10=rejected|11=settled|12=collected|13=returned|14=exported|15=password_changed|16=activated|17=deactivated');
            $table->string('auditable_type', 100)->index('idx_al_type');
            $table->char('auditable_id', 36)->index('idx_al_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_al_created');

            $table->index(['auditable_type', 'auditable_id'], 'idx_al_subject');
            $table->index(['user_id', 'created_at'], 'idx_al_actor_timeline');
            $table->index(['event', 'created_at'], 'idx_al_event_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('messages', 'attachment_url')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('attachment_url');
            });
        }
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('attachment_url', 500)->nullable()->after('message_type');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex('idx_conv_part_user_last_read');
            $table->dropColumn('last_read_at');
        });
    }
};

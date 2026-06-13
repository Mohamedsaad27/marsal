<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('account_type')
                ->nullable()
                ->after('is_active')
                ->comment('AccountTypeEnum: 1=super_admin|2=shipping_company|3=delivery_agent|4=staff_member');

            $table->index('account_type', 'idx_users_account_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_account_type');
            $table->dropColumn('account_type');
        });
    }
};

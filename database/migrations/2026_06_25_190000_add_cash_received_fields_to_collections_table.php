<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->timestamp('cash_received_at')->nullable()->after('settlement_id');
            $table->string('cash_received_by', 36)->nullable()->after('cash_received_at');

            $table->foreign('cash_received_by', 'fk_collections_cash_received_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index('cash_received_at', 'idx_collections_cash_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign('fk_collections_cash_received_by');
            $table->dropIndex('idx_collections_cash_received_at');
            $table->dropColumn(['cash_received_at', 'cash_received_by']);
        });
    }
};

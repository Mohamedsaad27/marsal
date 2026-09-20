<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->uuid('settlement_item_id')->primary();
            $table->uuid('settlement_id');
            $table->uuid('collection_id');
            $table->unsignedTinyInteger('settlement_type')
                ->comment('1=agent|2=company — SettlementTypeEnum');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('net_amount', 12, 2)
                ->comment('Signed snapshot amount for this settlement side');
            $table->timestamps();

            $table->foreign('settlement_id', 'fk_settlement_items_settlement')
                ->references('settlement_id')->on('settlements')->restrictOnDelete();
            $table->foreign('collection_id', 'fk_settlement_items_collection')
                ->references('collection_id')->on('collections')->restrictOnDelete();

            $table->unique(
                ['collection_id', 'settlement_type'],
                'uq_settlement_items_collection_type',
            );
            $table->index('settlement_id', 'idx_settlement_items_settlement');
            $table->index(
                ['settlement_id', 'settlement_type'],
                'idx_settlement_items_settlement_type',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
    }
};

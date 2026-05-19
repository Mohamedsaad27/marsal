<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('collection_id')->primary();
            $table->foreignUuid('order_id')->nullable()->unique()->references('order_id')->on('orders')->onDelete('set null');
            $table->foreignUuid('delivery_agent_id')->nullable()->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
            $table->foreignUuid('shipping_company_id')->nullable()->references('shipping_company_id')->on('shipping_companies')->onDelete('set null');
            $table->tinyInteger('collection_type')->default(1)->comment('1=cod|2=shipping_fee|3=partial — CollectionTypeEnum');
            $table->decimal('collected_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('net_due', 15, 2)->comment('collected_amount − commission_amount');
            $table->foreignUuid('settlement_id')->nullable()->references('settlement_id')->on('settlements')->onDelete('set null')->comment('FK → settlements; NULL = not yet settled');
            $table->timestamp('collected_at')->useCurrent();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['delivery_agent_id', 'settlement_id']);
            $table->index(['shipping_company_id', 'settlement_id']);
            $table->index('order_id');
            $table->index('delivery_agent_id');
            $table->index('shipping_company_id');
            $table->index('collection_type');
            $table->index('settlement_id');
            $table->index('collected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
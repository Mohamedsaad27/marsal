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
            $table->foreignUuid('order_id')->nullable();
            $table->foreignUuid('delivery_agent_id')->nullable();
            $table->foreignUuid('shipping_company_id')->nullable();
            $table->unsignedTinyInteger('collection_type')->default(1)
                ->comment('1=cod|2=shipping_fee|3=partial — CollectionTypeEnum');
            $table->decimal('collected_amount', 12, 2)->default(0);
            $table->decimal('agent_commission_amount', 12, 2)->default(0)
                ->comment('Delivery agent commission snapshot at collection time');
            $table->decimal('agent_net_due', 12, 2)->default(0)
                ->comment('collected_amount - agent_commission_amount; signed');
            $table->decimal('system_commission_amount', 12, 2)->default(0)
                ->comment('System commission snapshot at collection time');
            $table->decimal('company_net_due', 12, 2)->default(0)
                ->comment('collected_amount - system_commission_amount; signed');
            $table->timestamp('collected_at')->useCurrent();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('order_id', 'uq_collections_order');
            $table->index(['delivery_agent_id', 'collected_at'], 'idx_collections_agent_collected');
            $table->index(['shipping_company_id', 'collected_at'], 'idx_collections_company_collected');
            $table->index('collection_type', 'idx_collections_type');
            $table->index('collected_at', 'idx_collections_collected_at');

            $table->foreign('order_id', 'fk_collections_order')
                ->references('order_id')->on('orders')->nullOnDelete();
            $table->foreign('delivery_agent_id', 'fk_collections_delivery_agent')
                ->references('delivery_agent_id')->on('delivery_agents')->nullOnDelete();
            $table->foreign('shipping_company_id', 'fk_collections_shipping_company')
                ->references('shipping_company_id')->on('shipping_companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};

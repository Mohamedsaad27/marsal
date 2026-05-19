<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('order_id')->primary();
            $table->string('reference_no', 80)
                  ->comment('External order ref supplied by shipping company');
            $table->string('internal_code', 40)->unique()
                  ->comment('ShipOps internal unique tracking code');
            $table->foreignUuid('shipping_company_id')->nullable()
                  ->references('shipping_company_id')->on('shipping_companies')->onDelete('set null');
            $table->foreignUuid('delivery_agent_id')->nullable()
                  ->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
            $table->tinyInteger('status')->default(1);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('reference_no',       'idx_orders_ref');
            $table->index('shipping_company_id','idx_orders_company');
            $table->index('delivery_agent_id',  'idx_orders_agent');
            $table->index('status',              'idx_orders_status');
            $table->index('created_at',         'idx_orders_created');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

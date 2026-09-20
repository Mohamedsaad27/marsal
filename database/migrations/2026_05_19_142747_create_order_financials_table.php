<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_financials', function (Blueprint $table) {
            $table->uuid('order_financial_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->cascadeOnDelete();
            $table->decimal('original_amount', 15, 2)->default(0)
                ->comment('Amount stated by shipping company at creation');
            $table->decimal('approved_amount', 15, 2)->nullable()
                ->comment('Set after price-change approval; NULL = not changed');
            $table->decimal('collected_amount', 15, 2)->nullable()
                ->comment('Actual cash received from customer by agent');
            $table->decimal('shipping_fee', 15, 2)->nullable()
                ->comment('Applicable only in refused_paid_shipping scenario');
            $table->decimal('agent_commission_amount', 12, 2)->nullable()
                ->comment('Delivery agent commission snapshot at collection time');
            $table->decimal('system_commission_amount', 12, 2)->nullable()
                ->comment('System commission snapshot at collection time');
            $table->decimal('net_due_company', 12, 2)->nullable()
                ->comment('collected_amount - system_commission_amount; signed');
            $table->boolean('is_settled')->default(false)
                ->comment('1 = agent and company settlements are both paid');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'is_settled']);
            $table->index('is_settled');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_financials');
    }
};

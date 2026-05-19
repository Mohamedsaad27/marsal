<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->uuid('settlement_id')->primary();
            $table->tinyInteger('settlement_type')->comment('1=agent|2=company — SettlementTypeEnum');
            $table->tinyInteger('settlement_status')->default(1)->comment('1=draft|2=approved|3=paid — SettlementStatusEnum');

            // Exactly one of these two will be non-null depending on settlement_type.
            $table->foreignUuid('delivery_agent_id')->nullable()
                  ->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
            $table->foreignUuid('shipping_company_id')->nullable()
                  ->references('shipping_company_id')->on('shipping_companies')->onDelete('set null');

            $table->foreignUuid('initiated_by')->nullable()->references('user_id')->on('users')->onDelete('set null');
            $table->decimal('total_collections', 15, 2)->default(0);
            $table->decimal('total_commissions', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0)->comment('total_collections − total_commissions');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('payment_method', 100)->nullable()->comment('e.g. bank_transfer, cash, instapay');
            $table->string('payment_reference', 200)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['settlement_type', 'settlement_status']);
            $table->index('settlement_type');
            $table->index('delivery_agent_id');
            $table->index('shipping_company_id');
            $table->index('settlement_status');
            $table->index('initiated_by');
            $table->index('period_from');
            $table->index('period_to');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->uuid('return_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->foreignUuid('delivery_agent_id')->nullable()->references('delivery_agent_id')->on('delivery_agents')->onDelete('set null');
            $table->foreignUuid('shipping_company_id')->nullable()->references('shipping_company_id')->on('shipping_companies')->onDelete('set null');
            $table->tinyInteger('return_status')->default(1)->comment('1=pending|2=received_by_admin|3=sent_to_company — ReturnStatusEnum');
            $table->integer('returned_quantity')->default(1);
            $table->string('return_reason', 255)->nullable();
            $table->timestamp('received_at')->nullable()->comment('When admin physically received the goods');
            $table->timestamp('returned_to_company_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['delivery_agent_id', 'return_status'], 'idx_return_agent_status');
            $table->index(['shipping_company_id', 'return_status'], 'idx_return_company_status');
            $table->index('order_id');
            $table->index('delivery_agent_id');
            $table->index('shipping_company_id');
            $table->index('return_status');
            $table->index('received_at');
            $table->index('returned_to_company_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};

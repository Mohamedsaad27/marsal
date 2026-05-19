<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('order_item_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->text('item_description')->nullable();
            $table->unsignedInteger('total_quantity')->default(1);
            $table->unsignedInteger('delivered_quantity')->nullable();
            $table->unsignedInteger('returned_quantity')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('order_id');
            $table->index('total_quantity');
            $table->index('delivered_quantity');
            $table->index('returned_quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

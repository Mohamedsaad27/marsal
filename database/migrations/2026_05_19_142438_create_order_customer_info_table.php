<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_customer_info', function (Blueprint $table) {
            $table->uuid('order_customer_info_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->string('customer_name', 200);
            $table->string('customer_phone', 20);
            $table->string('phone_alt', 20)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'customer_phone']);
            $table->index('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_customer_info');
    }
};

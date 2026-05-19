<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->uuid('order_address_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->foreignUuid('governorate_id')->nullable()->references('governorate_id')->on('governorates')->onDelete('set null');
            $table->foreignUuid('city_id')->nullable()->references('city_id')->on('cities')->onDelete('set null');
            $table->text('address_line');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'governorate_id', 'city_id']);
            $table->index('governorate_id');
            $table->index('city_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};

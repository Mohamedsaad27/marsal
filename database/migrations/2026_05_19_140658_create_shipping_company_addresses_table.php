<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_company_addresses', function (Blueprint $table) {
            $table->uuid('shipping_company_address_id')->primary();
            $table->foreignUuid('shipping_company_id')->references('shipping_company_id')->on('shipping_companies')->onDelete('cascade');
            $table->foreignUuid('city_id')->nullable()->references('city_id')->on('cities')->onDelete('set null');
            $table->foreignUuid('governorate_id')->nullable()->references('governorate_id')->on('governorates')->onDelete('set null');
            $table->string('address_line')->nullable();
            $table->string('landmark')->nullable();
            $table->string('street')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['shipping_company_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_company_addresses');
    }
};

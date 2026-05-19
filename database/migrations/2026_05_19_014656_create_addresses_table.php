<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('address_id')->primary();
            $table->foreignUuid('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreignUuid('city_id')->nullable()->references('city_id')->on('cities')->onDelete('set null');
            $table->string('address_line');
            $table->string('landmark')->nullable();
            $table->string('street')->nullable();
            $table->string('building_number')->nullable();
            $table->string('floor_number')->nullable();
            $table->string('apartment_number')->nullable();
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

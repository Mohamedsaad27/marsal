<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_zones', function (Blueprint $table) {
            $table->uuid('agent_zone_id')->primary();
            $table->foreignUuid('delivery_agent_id')->references('delivery_agent_id')->on('delivery_agents')->onDelete('cascade');
            $table->foreignUuid('governorate_id')->nullable()->references('governorate_id')->on('governorates')->onDelete('set null');
            $table->foreignUuid('city_id')->nullable()->references('city_id')->on('cities')->onDelete('set null');
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_zones');
    }
};

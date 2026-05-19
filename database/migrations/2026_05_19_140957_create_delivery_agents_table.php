<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_agents', function (Blueprint $table) {
            $table->uuid('delivery_agent_id')->primary();
            $table->foreignUuid('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->string('national_id', 20)->nullable()->unique()
                  ->comment('14-digit Egyptian national ID');
            $table->tinyInteger('vehicle_type')->nullable()
                  ->comment('1=motorcycle|2=car|3=van|4=bicycle|5=on_foot');
            $table->string('vehicle_plate_number', 30)->nullable();
            $table->tinyInteger('commission_type')->default(1)
                  ->comment('1=percentage|2=fixed');
            $table->decimal('commission_value', 15, 4)->default(0);
            $table->decimal('balance', 15, 2)->default(0)
                  ->comment('Cash collected but not yet handed to admin');
            $table->tinyInteger('is_available')->default(1)
                  ->comment('0 = off-duty / suspended');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_agents');
    }
};

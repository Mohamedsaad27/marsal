<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('system_setting_id')->primary();
            $table->string('key', 100)->unique();
            $table->string('value', 255)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['key', 'value']);
            $table->index('key');
            $table->index('value');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

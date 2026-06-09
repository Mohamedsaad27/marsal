<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('department_id')->primary();
            $table->string('name_ar', 150)->unique();
            $table->string('name_en', 150)->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('manager_id')
                ->nullable()
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('manager_id', 'idx_departments_manager_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};

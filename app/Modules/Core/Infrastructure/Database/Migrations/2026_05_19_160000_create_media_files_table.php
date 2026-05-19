<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->uuid('media_file_id')->primary();
            $table->string('model_type', 80);
            $table->uuid('model_id');
            $table->string('disk', 40);
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('collection', 80)->default('default');
            $table->string('mime_type', 120)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['model_type', 'model_id'], 'idx_media_owner');
            $table->index('collection', 'idx_media_collection');
            $table->index('tenant_id', 'idx_media_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};

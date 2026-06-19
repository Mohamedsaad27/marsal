<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_proofs', function (Blueprint $table) {
            $table->uuid('order_proof_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->references('user_id')->on('users')->onDelete('set null');
            $table->tinyInteger('file_type')->default(1)->comment('1=image|2=pdf|3=other — ProofFileTypeEnum');
            $table->string('file_url', 500);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'uploaded_by', 'file_type']);
            $table->index('order_id');
            $table->index('uploaded_by');
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_proofs');
    }
};

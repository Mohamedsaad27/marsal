<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_approvals', function (Blueprint $table) {
            $table->uuid('order_approval_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->boolean('requires_approval')->default(true);
            $table->tinyInteger('approval_granted')->nullable()
                  ->comment('NULL=pending | 1=approved | 0=rejected');
            $table->foreignUuid('approved_by')->nullable()->references('user_id')->on('users')->onDelete('set null')
                  ->comment('user_id of the approver (shipping company user)');
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['order_id', 'approval_granted']);
            $table->index('approval_granted');
            $table->index('approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_approvals');
    }
};

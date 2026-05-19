<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->uuid('approval_request_id')->primary();
            $table->foreignUuid('order_id')->nullable()->references('order_id')->on('orders')->onDelete('set null');
            $table->tinyInteger('approval_type')->default(1)->comment('1=price_change|2=shipping_fee|3=partial_amount — ApprovalTypeEnum');
            $table->tinyInteger('approval_status')->default(1)->comment('1=pending|2=approved|3=rejected|4=expired — ApprovalStatusEnum');
            $table->foreignUuid('requested_by')->nullable()->references('user_id')->on('users')->onDelete('set null')->comment('Admin user_id; triggers on behalf of the agent');
            $table->foreignUuid('reviewed_by')->nullable()->references('user_id')->on('users')->onDelete('set null')->comment('Shipping company user_id who approved/rejected');
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->decimal('requested_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('expires_at')->nullable()
                  ->comment('Auto-expire if no response within N minutes');
            $table->timestamp('reviewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['order_id', 'approval_status']);
            $table->index(['approval_status', 'expires_at']);
            $table->index('order_id');
            $table->index('approval_type');
            $table->index('approval_status');
            $table->index('requested_by');
            $table->index('reviewed_by');
            $table->index('expires_at');
            $table->index('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};

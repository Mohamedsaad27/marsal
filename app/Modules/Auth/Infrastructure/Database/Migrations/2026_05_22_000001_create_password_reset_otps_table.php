<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('email');
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'fk_pro_user')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();
            $table->index(['email', 'expires_at'], 'idx_pro_email_expires');
            $table->index('user_id', 'idx_pro_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};

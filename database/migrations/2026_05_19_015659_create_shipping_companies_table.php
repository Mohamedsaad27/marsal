<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_companies', function (Blueprint $table) {
            $table->uuid('shipping_company_id')->primary();
            $table->foreignUuid('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->string('company_name', 200);
            $table->string('commercial_reg', 100)->nullable()
                  ->comment('Egyptian commercial registration number');
            $table->string('logo_url', 500)->nullable();
            $table->tinyInteger('commission_type')->default(1)
                  ->comment('1=percentage|2=fixed — CommissionTypeEnum');
            $table->decimal('commission_value', 10, 4)->default(0);
            $table->decimal('balance', 15, 2)->default(0)
                  ->comment('Accumulated net due; cleared on settlement');
            $table->tinyInteger('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_companies');
    }
};

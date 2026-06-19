<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1 — add reference_code as nullable so existing rows are not rejected.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference_code', 100)
                  ->nullable()
                  ->unique('uq_orders_reference_code')
                  ->comment('Auto-generated unique code: {COMPANY_PREFIX}-{D-M-YYYY}({EXCEL_CODE})')
                  ->after('internal_code');
        });

        // Step 2 — back-fill existing rows using the old internal_code value
        //           so the NOT NULL constraint can be applied safely.
        DB::statement("UPDATE `orders` SET `reference_code` = `internal_code` WHERE `reference_code` IS NULL");

        // Step 3 — make NOT NULL now that every row has a value.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference_code', 100)
                  ->nullable(false)
                  ->change();
        });

        // Step 4 — drop the old column.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_internal_code_unique');
            $table->dropColumn('internal_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('internal_code', 40)
                  ->nullable()
                  ->unique()
                  ->after('reference_no');
        });

        DB::statement("UPDATE `orders` SET `internal_code` = `reference_code` WHERE `internal_code` IS NULL");

        Schema::table('orders', function (Blueprint $table) {
            $table->string('internal_code', 40)->nullable(false)->change();
            $table->dropUnique('uq_orders_reference_code');
            $table->dropColumn('reference_code');
        });
    }
};

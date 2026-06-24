<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('delivery_agents')
            ->where('commission_type', 1)
            ->update(['commission_type' => 2]);

        DB::table('shipping_companies')
            ->where('commission_type', 1)
            ->update(['commission_type' => 2]);

        DB::statement('ALTER TABLE delivery_agents MODIFY commission_type TINYINT NOT NULL DEFAULT 2 COMMENT \'2=fixed — CommissionTypeEnum\'');
        DB::statement('ALTER TABLE shipping_companies MODIFY commission_type TINYINT NOT NULL DEFAULT 2 COMMENT \'2=fixed — CommissionTypeEnum\'');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE delivery_agents MODIFY commission_type TINYINT NOT NULL DEFAULT 1 COMMENT \'1=percentage|2=fixed — CommissionTypeEnum\'');
        DB::statement('ALTER TABLE shipping_companies MODIFY commission_type TINYINT NOT NULL DEFAULT 1 COMMENT \'1=percentage|2=fixed — CommissionTypeEnum\'');
    }
};

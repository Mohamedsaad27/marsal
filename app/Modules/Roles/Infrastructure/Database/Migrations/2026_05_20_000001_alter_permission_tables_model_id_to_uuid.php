<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $column = config('permission.column_names.model_morph_key', 'model_id');

        $this->alterPivotTable(config('permission.table_names.model_has_roles'), $column);
        $this->alterPivotTable(config('permission.table_names.model_has_permissions'), $column);
    }

    public function down(): void
    {
        $column = config('permission.column_names.model_morph_key', 'model_id');

        $this->revertPivotTable(config('permission.table_names.model_has_roles'), $column);
        $this->revertPivotTable(config('permission.table_names.model_has_permissions'), $column);
    }

    protected function alterPivotTable(string $table, string $column): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->uuid($column)->change();
        });
    }

    protected function revertPivotTable(string $table, string $column): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->unsignedBigInteger($column)->change();
        });
    }
};

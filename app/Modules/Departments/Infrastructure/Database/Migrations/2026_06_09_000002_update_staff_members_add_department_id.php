<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->foreignUuid('department_id')
                ->nullable()
                ->after('user_id')
                ->references('department_id')
                ->on('departments')
                ->nullOnDelete();

            $table->index('department_id', 'idx_sm_department_id');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('department', 100)
                ->nullable()
                ->after('user_id')
                ->comment('e.g. finance, operations, support, logistics');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex('idx_sm_department_id');
            $table->dropColumn('department_id');
        });
    }
};

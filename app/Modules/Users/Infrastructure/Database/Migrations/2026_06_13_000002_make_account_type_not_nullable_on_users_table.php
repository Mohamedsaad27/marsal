<?php

use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->with(['roles', 'staffMember', 'shippingCompany', 'deliveryAgent'])
            ->whereNull('account_type')
            ->each(function (User $user) {
                $type = $user->resolveAccountType();

                if ($type !== null) {
                    $user->updateQuietly(['account_type' => $type->value]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('account_type')
                ->nullable(false)
                ->comment('AccountTypeEnum: 1=super_admin|2=shipping_company|3=delivery_agent|4=staff_member')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('account_type')
                ->nullable()
                ->comment('AccountTypeEnum: 1=super_admin|2=shipping_company|3=delivery_agent|4=staff_member')
                ->change();
        });
    }
};

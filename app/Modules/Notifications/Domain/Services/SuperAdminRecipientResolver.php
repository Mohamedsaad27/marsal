<?php

namespace App\Modules\Notifications\Domain\Services;

use App\Modules\Users\Infrastructure\Database\Models\User;

class SuperAdminRecipientResolver
{
    /**
     * @return list<string>
     */
    public function activeUserIds(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas(
                'roles',
                fn ($query) => $query
                    ->where('name', 'super_admin')
                    ->where('guard_name', 'api'),
            )
            ->pluck('user_id')
            ->values()
            ->all();
    }
}

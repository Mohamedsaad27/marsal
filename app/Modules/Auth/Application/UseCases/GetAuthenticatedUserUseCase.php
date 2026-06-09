<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Users\Infrastructure\Database\Models\User;

class GetAuthenticatedUserUseCase
{
    public function execute(): User
    {
        /** @var User $user */
        $user = auth('api')->user();

        return $user->load(['shippingCompany', 'deliveryAgent', 'staffMember.department']);
    }
}

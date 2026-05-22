<?php

use App\Modules\Users\Presentation\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::post('users', [AdminUserController::class, 'store'])
            ->middleware('permission:users.create');

        Route::post('shipping-companies', [AdminUserController::class, 'storeShippingCompany'])
            ->middleware('permission:shipping_companies.create');

        Route::post('delivery-agents', [AdminUserController::class, 'storeDeliveryAgent'])
            ->middleware('permission:delivery_agents.create');

        Route::post('staff-members', [AdminUserController::class, 'storeStaffMember'])
            ->middleware('permission:users.create');
    });

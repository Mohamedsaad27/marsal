<?php

use App\Modules\Users\Presentation\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/admin')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('users', [AdminUserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('admin.users.index');

        Route::get('staff-members', [AdminUserController::class, 'indexStaffMembers'])
            ->middleware('permission:users.view')
            ->name('admin.staff-members.index');

        Route::get('shipping-companies', [AdminUserController::class, 'indexShippingCompanies'])
            ->middleware('permission:shipping_companies.view')
            ->name('admin.shipping-companies.index');

        Route::get('delivery-agents', [AdminUserController::class, 'indexDeliveryAgents'])
            ->middleware('permission:delivery_agents.view')
            ->name('admin.delivery-agents.index');

        Route::get('users/import/template', [AdminUserController::class, 'importTemplate'])
            ->middleware('permission:users.import')
            ->name('admin.users.importTemplate');

        Route::post('users/import', [AdminUserController::class, 'import'])
            ->middleware('permission:users.import')
            ->name('admin.users.import');

        Route::post('users', [AdminUserController::class, 'store'])
            ->middleware('permission:users.create');

        Route::put('users/{userId}', [AdminUserController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('admin.users.update');

        Route::patch('users/{userId}/toggle-status', [AdminUserController::class, 'toggleStatus'])
            ->middleware('permission:users.toggle')
            ->name('admin.users.toggleStatus');

        Route::delete('users/{userId}', [AdminUserController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('admin.users.destroy');

        Route::post('shipping-companies', [AdminUserController::class, 'storeShippingCompany'])
            ->middleware('permission:shipping_companies.create');

        Route::post('delivery-agents', [AdminUserController::class, 'storeDeliveryAgent'])
            ->middleware('permission:delivery_agents.create');

        Route::post('staff-members', [AdminUserController::class, 'storeStaffMember'])
            ->middleware('permission:users.create');

        Route::put('users/{userId}/change-password', [AdminUserController::class, 'changePassword'])
            ->middleware('permission:users.change_password')
            ->name('admin.users.changePassword');
    });

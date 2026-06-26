<?php

use App\Modules\Orders\Presentation\Http\Controllers\CompanyDashboardController;
use App\Modules\Orders\Presentation\Http\Controllers\CompanyOrderController;
use App\Modules\Orders\Presentation\Http\Controllers\CompanyProfileController;
use App\Modules\Orders\Presentation\Http\Controllers\CompanyWalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Orders API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/company
| Middleware: auth:api + role:shipping_company
*/

Route::prefix('api/v1/company')
    ->middleware(['auth:api', 'role:shipping_company'])
    ->group(function () {

        Route::get('dashboard', [CompanyDashboardController::class, 'index'])
            ->name('company.dashboard');

        Route::get('orders', [CompanyOrderController::class, 'index'])
            ->name('company.orders.index');

        Route::get('orders/{orderId}', [CompanyOrderController::class, 'show'])
            ->name('company.orders.show');

        Route::get('wallet', [CompanyWalletController::class, 'index'])
            ->name('company.wallet');

        Route::get('profile', [CompanyProfileController::class, 'index'])
            ->name('company.profile');
    });

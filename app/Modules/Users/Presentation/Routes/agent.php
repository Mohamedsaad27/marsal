<?php

use App\Modules\Users\Presentation\Http\Controllers\AgentProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Profile API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/agent
| Middleware: auth:api + role:delivery_agent
*/

Route::prefix('api/v1/agent')
    ->middleware(['auth:api', 'role:delivery_agent'])
    ->group(function () {
        Route::get('profile', [AgentProfileController::class, 'show'])
            ->name('agent.profile.show');

        Route::patch('fcm-token', [AgentProfileController::class, 'updateFcmToken'])
            ->name('agent.fcm-token.update');
    });

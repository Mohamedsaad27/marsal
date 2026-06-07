<?php

use App\Modules\Users\Presentation\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')
    ->middleware('auth:api')
    ->group(function () {
        Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
    });

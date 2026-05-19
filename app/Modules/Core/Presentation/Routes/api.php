<?php

use App\Modules\Core\Infrastructure\Helpers\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('core')->group(function () {
    Route::get('health', fn () => ApiResponse::success(['status' => 'ok', 'module' => 'core']));
});
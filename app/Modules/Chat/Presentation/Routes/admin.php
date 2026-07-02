<?php

use App\Modules\Chat\Presentation\Http\Controllers\AdminChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Chat API Routes (monitor only — read access)
|--------------------------------------------------------------------------
| Base path : /api/v1/admin/chat
*/

Route::prefix('api/v1/admin/chat')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('conversations', [AdminChatController::class, 'index'])
            ->middleware('permission:chat.view')
            ->name('admin.chat.conversations.index');

        Route::get('orders/{orderId}', [AdminChatController::class, 'getByOrder'])
            ->middleware('permission:chat.view')
            ->name('admin.chat.orders.show');

        Route::get('conversations/{conversationId}', [AdminChatController::class, 'show'])
            ->middleware('permission:chat.view')
            ->name('admin.chat.conversations.show');

        Route::get('conversations/{conversationId}/messages', [AdminChatController::class, 'messages'])
            ->middleware('permission:chat.view')
            ->name('admin.chat.messages.index');
    });

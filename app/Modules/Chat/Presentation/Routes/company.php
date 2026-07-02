<?php

use App\Modules\Chat\Presentation\Http\Controllers\ChatController;
use App\Modules\Chat\Presentation\Http\Controllers\OrderChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Chat API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/company/chat
*/

Route::prefix('api/v1/company/chat')
    ->middleware(['auth:api', 'role:shipping_company'])
    ->group(function () {
        Route::get('conversations', [ChatController::class, 'index'])
            ->name('company.chat.conversations.index');

        Route::get('orders/{orderId}', [OrderChatController::class, 'show'])
            ->name('company.chat.orders.show');

        Route::get('conversations/{conversationId}', [ChatController::class, 'show'])
            ->name('company.chat.conversations.show');

        Route::get('conversations/{conversationId}/messages', [ChatController::class, 'messages'])
            ->name('company.chat.messages.index');

        Route::post('conversations/{conversationId}/messages', [ChatController::class, 'send'])
            ->name('company.chat.messages.send');

        Route::patch('conversations/{conversationId}/read', [ChatController::class, 'markRead'])
            ->name('company.chat.conversations.read');
    });

<?php

use App\Modules\Chat\Presentation\Http\Controllers\ChatController;
use App\Modules\Chat\Presentation\Http\Controllers\OrderChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Chat API Routes
|--------------------------------------------------------------------------
| Base path : /api/v1/agent/chat
*/

Route::prefix('api/v1/agent/chat')
    ->middleware(['auth:api', 'role:delivery_agent'])
    ->group(function () {
        Route::get('conversations', [ChatController::class, 'index'])
            ->name('agent.chat.conversations.index');

        Route::get('orders/{orderId}', [OrderChatController::class, 'show'])
            ->name('agent.chat.orders.show');

        Route::get('conversations/{conversationId}', [ChatController::class, 'show'])
            ->name('agent.chat.conversations.show');

        Route::get('conversations/{conversationId}/messages', [ChatController::class, 'messages'])
            ->name('agent.chat.messages.index');

        Route::post('conversations/{conversationId}/messages', [ChatController::class, 'send'])
            ->name('agent.chat.messages.send');

        Route::patch('conversations/{conversationId}/read', [ChatController::class, 'markRead'])
            ->name('agent.chat.conversations.read');
    });

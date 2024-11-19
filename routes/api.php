<?php

use App\Http\Controllers\Auth\loginController;
use App\Http\Controllers\Auth\registerController;
use App\Http\Controllers\Auth\logoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MessageController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Chat routes
    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [MessageController::class, 'getConversations']);
        Route::get('/{userId}', [MessageController::class, 'getMessages']);
        Route::post('/send', [MessageController::class, 'sendMessage']);
        Route::put('/{messageId}/read', [MessageController::class, 'markAsRead']);
    });
});
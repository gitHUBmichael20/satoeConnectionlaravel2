<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\registerController;
use App\Http\Controllers\Auth\loginController;
use App\Http\Controllers\Auth\logoutController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', registerController::class);
Route::post('/login', loginController::class);
Route::post('/logout', logoutController::class)->middleware('auth:sanctum');
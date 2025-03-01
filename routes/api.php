<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrder\TravelOrderController;

// Rotas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// // Rotas protegidas por JWT
Route::middleware('auth.jwt')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('travel-orders', TravelOrderController::class)->except('create', 'edit');
});

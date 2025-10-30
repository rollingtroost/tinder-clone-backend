<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public auth endpoints
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

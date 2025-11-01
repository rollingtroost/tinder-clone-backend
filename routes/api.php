<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SwipeController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PersonController;

// Public auth endpoints
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/profile/user', [AuthController::class, 'me']);

    Route::post('/person', [PersonController::class, 'upsert']);

    Route::get('/recommendations', [RecommendationController::class, 'index']);

    Route::post('/swipes', [SwipeController::class, 'store'])->middleware('throttle:swipes');

    Route::get('/likes', [LikeController::class, 'index']);
});

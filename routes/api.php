<?php

use App\Http\Controllers\Api\AppAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileQueueController;
use App\Http\Controllers\Api\ProfileController;

//api login route
Route::post('/login', [AppAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AppAuthController::class, 'logout']);

    Route::get('/services', [MobileQueueController::class, 'services']);

    Route::get('/queues/today', [MobileQueueController::class, 'todayQueues']);

    Route::get('/dashboard', [MobileQueueController::class, 'dashboard']);

    Route::get('/queues/today/status', [MobileQueueController::class, 'getTodayQueueStatus']);

    Route::post('/queues', [MobileQueueController::class, 'store']);


});

Route::middleware('auth:sanctum')->group(function () {

    // Existing routes...

    Route::get('/profile', [ProfileController::class, 'show']);

    Route::put('/profile', [ProfileController::class,'update']);

    Route::put('/profile/password', [ProfileController::class, 'changePassword']);

});


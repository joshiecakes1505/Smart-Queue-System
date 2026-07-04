<?php

use App\Http\Controllers\Api\AppAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileQueueController;


//api login route
// Route::middleware('auth:sanctum')->group(function (){
//     Route::post('/login', [AppAuthController::class, 'login']);
// });

Route::post('/login', [AppAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/services', [MobileQueueController::class, 'services']);

    Route::get('/dashboard', [MobileQueueController::class, 'dashboard']);

    Route::post('/queues', [MobileQueueController::class, 'store']);

});


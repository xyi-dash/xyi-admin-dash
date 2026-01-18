<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\LogController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/account/profile', [AccountController::class, 'profile']);
    
    Route::get('/logs', [LogController::class, 'index']);
    Route::get('/logs/types', [LogController::class, 'types']);
    Route::get('/logs/person/{personId}/{server}', [LogController::class, 'byPerson']);
});

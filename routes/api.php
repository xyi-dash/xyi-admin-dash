<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
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

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::post('/auth', [AdminController::class, 'auth']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/me', [AdminController::class, 'me']);
    Route::get('/list', [AdminController::class, 'index']);
    Route::get('/{adminId}', [AdminController::class, 'show']);
});

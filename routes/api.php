<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminLogController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\NewsController;

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
    Route::get('/{adminId}', [AdminController::class, 'show'])->where('adminId', '[0-9]+');

    // 6+ logs
    Route::get('/logs/actions', [AdminLogController::class, 'adminActions']);
    Route::get('/logs/warnings', [AdminLogController::class, 'warnings']);
    Route::get('/logs/purchases', [AdminLogController::class, 'purchases']);
    Route::post('/logs/purchases/confirm', [AdminLogController::class, 'confirmPurchase']);
    
    // 7+ logs
    Route::get('/logs/removed', [AdminLogController::class, 'removedAdmins']);
    
    // 8lvl logs
    Route::get('/logs/ga-actions', [AdminLogController::class, 'gaActions']);
    
    // 8+ server management
    Route::get('/servers', [AdminLogController::class, 'serverSettings']);
    Route::post('/servers', [AdminLogController::class, 'updateServerSettings']);
    
    // 8+ news management
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{id}', [NewsController::class, 'update']);
    Route::delete('/news/{id}', [NewsController::class, 'destroy']);
});

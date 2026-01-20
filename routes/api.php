<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminLogController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\AdminManagementController;
use App\Http\Controllers\Api\PlayerLogController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

Route::post('/admin/exchange-token', [AuthController::class, 'exchangeToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/account/profile', [AccountController::class, 'profile']);
    Route::get('/logs', [LogController::class, 'index']);
    Route::get('/logs/types', [LogController::class, 'types']);
    Route::get('/logs/person/{personId}/{server}', [LogController::class, 'byPerson']);
    
    Route::post('/cp/prepare', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $adminSession = app(\App\Services\AdminSessionService::class);
        
        if (!$adminSession->hasAnyUnlocked($user)) {
            return response()->json(['error' => 'unlock a server first'], 403);
        }
        
        $token = encrypt($user->id . '|' . $user->server . '|' . time());
        return response()->json(['ok' => true, 'token' => base64_encode($token)]);
    });
    
    Route::post('/admin/prepare-redirect', [AuthController::class, 'prepareAdminRedirect']);
});

// pre-unlock: you know you're an admin but you can't prove it
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/auth', [AdminController::class, 'auth']);
    Route::get('/session/status', [AdminController::class, 'sessionStatus']);
});

// post-unlock
Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'admin.unlocked'])->group(function () {
    Route::get('/me', [AdminController::class, 'me']);
    Route::get('/list', [AdminController::class, 'index']);
    Route::get('/{adminId}', [AdminController::class, 'show'])->where('adminId', '[0-9]+');

    Route::post('/manage', [AdminManagementController::class, 'execute']);
    Route::get('/manage/{adminName}/actions', [AdminManagementController::class, 'availableActions']);
    Route::get('/manage/{adminName}/history', [AdminManagementController::class, 'history']);

    Route::get('/logs/actions', [AdminLogController::class, 'adminActions']);
    Route::get('/logs/warnings', [AdminLogController::class, 'warnings']);
    Route::get('/logs/purchases', [AdminLogController::class, 'purchases']);
    Route::post('/logs/purchases/confirm', [AdminLogController::class, 'confirmPurchase']);
    Route::get('/logs/removed', [AdminLogController::class, 'removedAdmins']);
    Route::get('/logs/ga-actions', [AdminLogController::class, 'gaActions']);
    
    Route::get('/servers', [AdminLogController::class, 'serverSettings']);
    Route::post('/servers', [AdminLogController::class, 'updateServerSettings']);
    
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{id}', [NewsController::class, 'update']);
    Route::delete('/news/{id}', [NewsController::class, 'destroy']);

    Route::get('/extended/servers', [PlayerLogController::class, 'availableServers']);
    
    Route::prefix('players')->group(function () {
        Route::get('/search', [PlayerLogController::class, 'searchPlayer']);
        Route::get('/{accountId}', [PlayerLogController::class, 'getPlayerStats'])->where('accountId', '[0-9]+');
    });

    Route::prefix('extended')->group(function () {
        Route::get('/reputation', [PlayerLogController::class, 'reputationLogs']);
        Route::get('/nicknames', [PlayerLogController::class, 'nicknameLogs']);
        Route::get('/unbans', [PlayerLogController::class, 'unbanLogs']);
        Route::get('/bans', [PlayerLogController::class, 'permanentBans']);
        Route::get('/ip-bans', [PlayerLogController::class, 'permanentIPBans']);
        Route::get('/matchmaking', [PlayerLogController::class, 'matchmakingStats']);
        Route::get('/money-transfers', [PlayerLogController::class, 'moneyTransferLogs']);
        Route::get('/accessories', [PlayerLogController::class, 'accessoryLogs']);
    });
});

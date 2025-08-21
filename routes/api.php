<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ApplicationGroupController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('status', [BaseApiController::class, 'status']);

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Applications
    Route::apiResource('applications', ApplicationController::class);
    Route::post('applications/{application}/check', [ApplicationController::class, 'healthCheck']);
    Route::get('applications/{application}/status', [ApplicationController::class, 'status']);
    Route::get('applications/{application}/subscribers', [ApplicationController::class, 'subscribers']);
    
    // Application Groups
    Route::apiResource('application-groups', ApplicationGroupController::class);
    Route::post('application-groups/{applicationGroup}/applications', [ApplicationGroupController::class, 'addApplication']);
    Route::delete('application-groups/{applicationGroup}/applications/{application}', [ApplicationGroupController::class, 'removeApplication']);
    Route::get('application-groups/{applicationGroup}/subscribers', [ApplicationGroupController::class, 'subscribers']);
    
    // Incidents
    Route::get('incidents/stats', [IncidentController::class, 'stats']);
    Route::apiResource('incidents', IncidentController::class);
    Route::put('incidents/{incident}/resolve', [IncidentController::class, 'resolve']);
    Route::put('incidents/{incident}/reopen', [IncidentController::class, 'reopen']);
    
    // Subscriptions
    Route::apiResource('subscriptions', SubscriptionController::class);
    
    // Notification settings
    Route::prefix('user')->group(function () {
        Route::get('notification-settings', [NotificationController::class, 'settings']);
        Route::put('notification-settings', [NotificationController::class, 'updateSettings']);
        Route::post('test-notification/{type}', [NotificationController::class, 'test']);
        Route::get('notification-history', [NotificationController::class, 'history']);
    });
});

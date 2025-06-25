<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\QueueController;
use App\Http\Controllers\API\QueueEntryController;
use App\Http\Controllers\API\CashierController;
use App\Http\Controllers\API\CustomerTrackingController;
use App\Http\Controllers\API\ScreenLayoutController;
use App\Http\Controllers\API\WidgetController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Hello World'
    ]);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::put('/user', [AuthController::class, 'updateUser']);

    // Queues - Complete CRUD and management operations
    Route::prefix('queues')->group(function () {
        Route::get('/', [QueueController::class, 'index']);
        Route::post('/createqueue', [QueueController::class, 'store']);
        Route::get('/{queue}', [QueueController::class, 'show']);
        Route::put('/{queue}', [QueueController::class, 'update']);
        Route::delete('/{queue}', [QueueController::class, 'destroy']);
        
        // Queue control operations
        Route::post('/{queue}/reset', [QueueController::class, 'reset']);
        Route::post('/{queue}/pause', [QueueController::class, 'pause']);
        Route::post('/{queue}/resume', [QueueController::class, 'resume']);
        Route::post('/{queue}/close', [QueueController::class, 'close']);
        Route::get('/{queue}/status', [QueueController::class, 'status']);
        
        // Queue number operations
        Route::post('/{queue}/call-next', [QueueController::class, 'callNext']);
        Route::post('/{queue}/skip', [QueueController::class, 'skip']);
        Route::post('/{queue}/recall', [QueueController::class, 'recall']);
        
        // Inventory management (for inventory queues)
        Route::post('/{queue}/adjust-stock', [QueueController::class, 'adjustStock']);
        Route::post('/{queue}/undo-last-entry', [QueueController::class, 'undoLastEntry']);
        
        // Queue data
        Route::get('/{queue}/entries', [QueueController::class, 'entries']);
        Route::get('/{queue}/analytics', [QueueController::class, 'analytics']);
    });

    // Queue Entries - Complete CRUD and management operations
    Route::prefix('entries')->group(function () {
        Route::get('/', [QueueEntryController::class, 'index']);
        Route::post('/', [QueueEntryController::class, 'store']);
        Route::get('/stats', [QueueEntryController::class, 'getStats']);
        Route::get('/search', [QueueEntryController::class, 'search']);
        Route::post('/bulk-update-status', [QueueEntryController::class, 'bulkUpdateStatus']);
        
        // Entry-specific operations
        Route::get('/{entry}', [QueueEntryController::class, 'show']);
        Route::put('/{entry}', [QueueEntryController::class, 'update']);
        Route::delete('/{entry}', [QueueEntryController::class, 'destroy']);
        Route::patch('/{entry}/status', [QueueEntryController::class, 'updateStatus']);
        Route::post('/{entry}/cancel', [QueueEntryController::class, 'cancel']);
        Route::get('/{entry}/timeline', [QueueEntryController::class, 'getTimeline']);
        
        // Status-based operations
        Route::get('/status/{status}', [QueueEntryController::class, 'getByStatus']);
        
        // Cashier-based operations
        Route::get('/cashier/{cashier_id}', [QueueEntryController::class, 'getByCashier']);
    });

    // Queue-specific entry operations
    Route::prefix('queues/{queue}/entries')->group(function () {
        Route::get('/active', [QueueEntryController::class, 'getActiveEntries']);
        Route::get('/next', [QueueEntryController::class, 'getNextEntry']);
    });

    // Cashiers
    Route::prefix('cashiers')->group(function () {
        Route::get('/', [CashierController::class, 'index']);
        Route::post('/', [CashierController::class, 'store']);
        Route::get('/{cashier}', [CashierController::class, 'show']);
        Route::put('/{cashier}', [CashierController::class, 'update']);
        Route::delete('/{cashier}', [CashierController::class, 'destroy']);
        Route::post('/{cashier}/assign', [CashierController::class, 'assignToQueue']);
        Route::post('/{cashier}/set-active', [CashierController::class, 'setActive']);
    });

    // Queues with cashiers
    Route::get('/queues-with-cashiers', [CashierController::class, 'queuesWithCashiers']);

    // QR Tracking
    Route::prefix('tracking')->group(function () {
        Route::get('/{entry_id}', [CustomerTrackingController::class, 'show']);
        Route::patch('/{entry_id}/status', [CustomerTrackingController::class, 'updateStatus']);
    });

    // Screen Layouts
    Route::prefix('layouts')->group(function () {
        Route::get('/', [ScreenLayoutController::class, 'index']);
        Route::post('/', [ScreenLayoutController::class, 'store']);
        Route::get('/{layout}', [ScreenLayoutController::class, 'show']);
        Route::put('/{layout}', [ScreenLayoutController::class, 'update']);
        Route::delete('/{layout}', [ScreenLayoutController::class, 'destroy']);
        Route::post('/{layout}/set-default', [ScreenLayoutController::class, 'setDefault']);
        Route::post('/{layout}/duplicate', [ScreenLayoutController::class, 'duplicate']);
        Route::get('/{layout}/preview', [ScreenLayoutController::class, 'preview']);
        Route::get('/device/{device_id}', [ScreenLayoutController::class, 'getByDevice']);
    });

    // Widgets
    Route::prefix('widgets')->group(function () {
        Route::get('/data', [WidgetController::class, 'fetch']);
        Route::get('/stats', [WidgetController::class, 'getStats']);
        Route::get('/real-time', [WidgetController::class, 'getRealTimeData']);
        Route::get('/preview', [WidgetController::class, 'getPreviewData']);
        Route::get('/type/{type}', [WidgetController::class, 'getByType']);
        Route::patch('/{widget}/settings', [WidgetController::class, 'updateSettings']);
    });

    // Layout-specific widget operations
    Route::prefix('layouts/{layout}/widgets')->group(function () {
        Route::get('/', [WidgetController::class, 'getByLayout']);
    });
});
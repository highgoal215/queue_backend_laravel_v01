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

// Handle preflight requests
Route::options('/{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');

Route::get('/', function () {
    return response()->json([
        'message' => 'Queue Management API',
        'version' => '1.0.0',
        'status' => 'active'
    ]);
});

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::delete('/user', [AuthController::class, 'deleteUser']);

    // Queues - Complete CRUD and management operations
    Route::prefix('queues')->group(function () {
        Route::get('/', [QueueController::class, 'index']);
        Route::post('/', [QueueController::class, 'store']);
        Route::get('/stats', [QueueController::class, 'getStats']);
        Route::get('/{queue}', [QueueController::class, 'show']);
        Route::put('/{queue}', [QueueController::class, 'update']);
        Route::delete('/{queue}', [QueueController::class, 'destroy']);
        Route::post('/{queue}/start', [QueueController::class, 'startQueue']);
        Route::post('/{queue}/stop', [QueueController::class, 'stopQueue']);
        Route::post('/{queue}/reset', [QueueController::class, 'resetQueue']);
        Route::post('/{queue}/next', [QueueController::class, 'callNext']);
        Route::post('/{queue}/recall', [QueueController::class, 'recallCurrent']);
        Route::get('/{queue}/entries', [QueueController::class, 'getEntries']);
        Route::get('/{queue}/active-entries', [QueueController::class, 'getActiveEntries']);
        Route::get('/{queue}/completed-entries', [QueueController::class, 'getCompletedEntries']);
        Route::get('/{queue}/wait-times', [QueueController::class, 'getWaitTimes']);
        Route::post('/{queue}/bulk-update', [QueueController::class, 'bulkUpdate']);
    });

    // Queue Entries - Complete CRUD and status management
    Route::prefix('entries')->group(function () {
        Route::get('/', [QueueEntryController::class, 'index']);
        Route::get('/all-details', [QueueEntryController::class, 'getAllEntriesWithDetails']);
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
        Route::get('/detailed', [CashierController::class, 'getDetailedInfo']);
        Route::get('/essential', [CashierController::class, 'getEssentialInfo']);
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
        Route::get('/', [WidgetController::class, 'index']);
        Route::post('/', [WidgetController::class, 'store']);
        Route::get('/{widget}', [WidgetController::class, 'show']);
        Route::put('/{widget}', [WidgetController::class, 'update']);
        Route::delete('/{widget}', [WidgetController::class, 'destroy']);
        Route::post('/{widget}/duplicate', [WidgetController::class, 'duplicate']);
        Route::get('/types', [WidgetController::class, 'getTypes']);
        Route::get('/templates', [WidgetController::class, 'getTemplates']);
    });
});

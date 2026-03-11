<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LivestockController;
use App\Http\Controllers\API\PenController;
use App\Http\Controllers\API\FeedController;
use App\Http\Controllers\API\PredictionController;
use App\Http\Controllers\API\DashboardController;

// Livestock
Route::apiResource('livestocks', LivestockController::class);
Route::post('livestocks/{livestock}/record-weight', [LivestockController::class, 'recordWeight']);
Route::get('livestocks/{livestock}/weight-history', [LivestockController::class, 'weightHistory']);

// Pens
Route::apiResource('pens', PenController::class);
Route::get('pens/{pen}/analytics', [PenController::class, 'analytics']);

// Feeds
Route::apiResource('feeds', FeedController::class);
Route::get('feeds/stock/summary', [FeedController::class, 'stockSummary']);
Route::get('feeds/requirements', [FeedController::class, 'requirements']);
Route::post('feeds/record-feeding', [FeedController::class, 'recordFeeding']);
Route::post('feeds/update-stock', [FeedController::class, 'updateStock']);

// Predictions
Route::prefix('predictions')->group(function () {
    Route::get('/', [PredictionController::class, 'index']);
    Route::get('/history', [PredictionController::class, 'history']);
    Route::get('/correlation', [PredictionController::class, 'correlation']);
    Route::post('/', [PredictionController::class, 'predict']);
});

// Dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('overview', [DashboardController::class, 'overview']);
    Route::get('pen-analytics', [DashboardController::class, 'penAnalytics']);
});

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
    ]);
});

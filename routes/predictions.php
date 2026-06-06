<?php

use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

// =============================================
// PREDICTION ROUTES (Integrasi ML)
// =============================================

Route::prefix('api')->group(function () {
    // Status ML Service bisa dicek tanpa login (untuk testing konektivitas)
    Route::get('/ml-status', [PredictionController::class, 'mlStatus']);

    // Fitur prediksi hanya untuk user login
    Route::middleware('auth')->group(function () {
        Route::post('/predictions', [PredictionController::class, 'predict']);
        Route::get('/predictions/history', [PredictionController::class, 'history']);
        Route::get('/predictions/{id}', [PredictionController::class, 'show']);
    });
});

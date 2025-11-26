<?php

use App\Http\Controllers\Api\PromoCodeController;
use Illuminate\Support\Facades\Route;

// Códigos promocionales
Route::prefix('promo-codes')->name('promo-codes.')->middleware('auth:sanctum')->group(function () {
    Route::post('/validate', [PromoCodeController::class, 'validate'])->name('validate');
    Route::post('/consume', [PromoCodeController::class, 'consume'])->name('consume');
    Route::post('/my-history', [PromoCodeController::class, 'myHistory'])->name('my-history');
});
// Fin códigos promocionales

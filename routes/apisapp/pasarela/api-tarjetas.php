<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Tarjetas
Route::prefix('me/cards')->name('cards.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/create', [PaymentController::class, 'store'])->name('store');
    Route::post('/stripe-intent', [PaymentController::class, 'createStripeIntent'])->name('stripe-intent');
    Route::post('/show', [PaymentController::class, 'show'])->name('show');
    Route::post('/update', [PaymentController::class, 'update'])->name('update');
    Route::post('/delete', [PaymentController::class, 'destroy'])->name('destroy');
    Route::post('/select', [PaymentController::class, 'selectPayment'])->name('select');
    Route::post('/default', [PaymentController::class, 'defaultPayment'])->name('default');
});



// Fin tarjetas

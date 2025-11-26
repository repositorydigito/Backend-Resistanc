<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;
// Rutas de Pedidos de Productos
Route::prefix('orders')->name('orders.')->middleware('auth:sanctum')->group(function () {
    Route::post('/my-orders', [OrderController::class, 'index'])->name('my-orders');
    Route::post('/create', [OrderController::class, 'store'])->name('store');
    Route::post('/show', [OrderController::class, 'show'])->name('show');
});
// Fin rutas de Pedidos de Productos


<?php

use App\Http\Controllers\Api\DrinkController;
use Illuminate\Support\Facades\Route;

// Órdenes de bebidas
Route::prefix('juice-orders')->name('juice-orders.')->middleware('auth:sanctum')->group(function () {
    Route::post('/my-orders', [DrinkController::class, 'myOrders'])->name('my-orders');
    Route::post('/show', [DrinkController::class, 'showOrder'])->name('show');
    // Route::post('/update-status', [DrinkController::class, 'updateOrderStatus'])->name('update-status');
});
// Fin órdenes de bebidas

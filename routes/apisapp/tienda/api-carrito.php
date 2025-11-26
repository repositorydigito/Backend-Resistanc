


<?php

use App\Http\Controllers\Api\ShoppingCartController;
use Illuminate\Support\Facades\Route;


// Carrito de compras
Route::prefix('shopping-cart')->name('shopping-cart.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ShoppingCartController::class, 'show'])->name('show');
    Route::post('/add', [ShoppingCartController::class, 'add'])->name('add');
    Route::post('/remove', [ShoppingCartController::class, 'remove'])->name('remove');
    Route::post('/update-quantity', [ShoppingCartController::class, 'updateQuantity'])->name('update-quantity');
    Route::post('/clear', [ShoppingCartController::class, 'clear'])->name('clear');
    Route::post('/confirm', [ShoppingCartController::class, 'confirm'])->name('confirm');
});
// Fin carrito

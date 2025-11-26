<?php

use App\Http\Controllers\Api\DrinkController;
use Illuminate\Support\Facades\Route;

// Bebidas
Route::prefix('drinks')->name('drinks.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [DrinkController::class, 'index'])->name('index');
    Route::post('/show', [DrinkController::class, 'show'])->name('show');
    Route::post('/base-drinks', [DrinkController::class, 'baseDrinks'])->name('base-drinks');
    Route::post('/flavor-drinks', [DrinkController::class, 'flavorDrinks'])->name('flavor-drinks');
    Route::post('/type-drinks', [DrinkController::class, 'typeDrinks'])->name('type-drinks');
    Route::post('/shakes/available', [DrinkController::class, 'availableMembershipShakes'])->name('available-shakes');
    // Carrito shakes
    Route::post('/cart/add', [DrinkController::class, 'addToCart'])->name('add-to-cart');
    Route::post('/cart/show', [DrinkController::class, 'showToCart'])->name('show-to-cart');
    Route::post('/cart/remove', [DrinkController::class, 'removeFromCart'])->name('remove-from-cart');
    Route::post('/cart/update-quantity', [DrinkController::class, 'updateCartQuantity'])->name('update-quantity');
    Route::post('/cart/confirm', [DrinkController::class, 'confirmCart'])->name('confirm-cart');
});

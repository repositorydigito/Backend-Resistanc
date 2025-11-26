<?php

use App\Http\Controllers\Api\FavoriteController;
use Illuminate\Support\Facades\Route;

// Favoritos
Route::prefix('favorites')->name('favorites.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [FavoriteController::class, 'index'])->name('index');
    Route::post('/drinks/add', [FavoriteController::class, 'storeDrink'])->name('favorite-drink-add');
    Route::post('/products', [FavoriteController::class, 'products'])->name('favorite-products');
    Route::post('/products/add', [FavoriteController::class, 'storeProduct'])->name('favorite-product-add');
    Route::post('/classes/add', [FavoriteController::class, 'storeClass'])->name('favorite-class-add');
    Route::post('/instructors/add', [FavoriteController::class, 'storeInstructor'])->name('favorite-instructor-add');
});
// Fin Favoritos

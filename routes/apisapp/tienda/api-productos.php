
<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Productos
Route::prefix('products')->name('products.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ProductController::class, 'index'])->name('index');
    Route::post('/show', [ProductController::class, 'show'])->name('show');
    Route::post('/categories/list', [ProductController::class, 'categories'])->name('categories');
    Route::post('/tags/list', [ProductController::class, 'tags'])->name('tags');
});
// Fin productos

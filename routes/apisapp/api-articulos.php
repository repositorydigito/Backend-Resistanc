<?php

use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

// Artículos
Route::prefix('posts')->name('posts.')->middleware('auth:sanctum')->group(function () {
    Route::post('/list', [PostController::class, 'index'])->name('index');
    Route::post('/show', [PostController::class, 'show'])->name('show');
    Route::post('/category/list', [PostController::class, 'categories'])->name('categories');
    Route::post('/tags/list', [PostController::class, 'tags'])->name('tags');
});
// Fin articulos

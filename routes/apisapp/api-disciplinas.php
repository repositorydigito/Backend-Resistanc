<?php

use App\Http\Controllers\Api\DisciplineController;
use Illuminate\Support\Facades\Route;

// Disciplinas
Route::prefix('disciplines')->name('disciplines.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [DisciplineController::class, 'index'])->name('index');
    Route::post('/group', [DisciplineController::class, 'indexGroup'])->name('indexGroup');
});
// Fin disciplinas

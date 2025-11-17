<?php

use App\Http\Controllers\Api\WaitingController;
use Illuminate\Support\Facades\Route;

// Lista de espera
Route::prefix('waiting-list')->name('waiting-list.')->middleware('auth:sanctum')->group(function () {
    Route::post('/list', [WaitingController::class, 'indexWaitingList'])->name('index');
    Route::post('create/', [WaitingController::class, 'addWaitingList'])->name('add');
    Route::post('/show', [WaitingController::class, 'show'])->name('show');
    Route::post('/check-status', [WaitingController::class, 'checkWaitingStatus'])->name('check-status');
    Route::post('/delete', [WaitingController::class, 'destroy'])->name('destroy');
});
// Fin lista de espera

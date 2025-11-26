<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FootwearController;

// Reservas de calzado
Route::prefix('footwear')->name('footwear.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [FootwearController::class, 'index'])->name('index');
    Route::post('/availability', [FootwearController::class, 'getAvailabilityForSchedule'])->name('availability');
    Route::post('/my-reservations', [FootwearController::class, 'getMyReservationsForSchedule'])->name('my-reservations');
    Route::post('/class-schedule', [FootwearController::class, 'indexClassSchedule'])->name('index-class-schedule');
    Route::post('/reserve', [FootwearController::class, 'reserve'])->name('reserve');
    Route::post('/update-reservation', [FootwearController::class, 'updateReservation'])->name('update-reservation');
    Route::post('/cancel-reservation', [FootwearController::class, 'cancelReservation'])->name('cancel-reservation');
});

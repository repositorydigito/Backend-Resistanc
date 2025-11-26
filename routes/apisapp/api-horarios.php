<?php

use App\Http\Controllers\Api\ClassScheduleController;
use Illuminate\Support\Facades\Route;


// Horarios
Route::prefix('class-schedules')->name('class-schedules.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ClassScheduleController::class, 'index'])->name('index');
    Route::post('/show', [ClassScheduleController::class, 'show'])->name('show');
    Route::post('/seat-map', [ClassScheduleController::class, 'getSeatMap'])->name('seat-map');
    Route::post('/check-packages', [ClassScheduleController::class, 'checkPackageAvailability'])->name('check-packages');
    Route::post('/reserve-seats', [ClassScheduleController::class, 'reserveSeats'])->name('reserve-seats');
    Route::post('/release-seats', [ClassScheduleController::class, 'releaseSeats'])->name('release-seats');
    // Route::post('/confirm-attendance', [ClassScheduleController::class, 'confirmAttendance'])->name('confirm-attendance');
    Route::post('/my-reservations', [ClassScheduleController::class, 'getMyReservations'])->name('my-reservations');
    // Route::post('/class-schedulesUser', [ClassScheduleController::class, 'classScheduleUser'])->name('class-schedules');
    // Route::post('/class-schedulesUserPending', [ClassScheduleController::class, 'classScheduleUserPending'])->name('class-schedules-pending');
    Route::post('/reserved-show', [ClassScheduleController::class, 'reservedShow'])->name('reserved-show');
});
// Fin Horarios

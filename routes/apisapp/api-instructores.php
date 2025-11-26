<?php

use App\Http\Controllers\Api\InstructorController;
use Illuminate\Support\Facades\Route;

// Instructores
Route::prefix('instructors')->name('instructors.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [InstructorController::class, 'index'])->name('index');
    Route::post('/week', [InstructorController::class, 'instructorsWeek'])->name('week');
    Route::post('/show', [InstructorController::class, 'show'])->name('show');
    Route::post('/rate', [InstructorController::class, 'scoreInstructor'])->name('favorite');
    Route::post('ten', [InstructorController::class, 'indexTen']);
});
// Fin instructores

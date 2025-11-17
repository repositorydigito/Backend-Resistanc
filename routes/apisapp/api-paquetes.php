<?php

use App\Http\Controllers\Api\PackageController;
use Illuminate\Support\Facades\Route;

// Paquetes
Route::prefix('packages')->name('packages.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [PackageController::class, 'index'])->name('index');
    Route::post('/show', [PackageController::class, 'show'])->name('show');
    Route::post('/me', [PackageController::class, 'packageMe'])->name('me');
    Route::post('/me/create', [PackageController::class, 'packageMeCreate'])->name('meCreate');
    Route::post('/me/vigent', [PackageController::class, 'packageMeVigent'])->name('packageMeVigent');
    Route::post('/me/redeem-shake', [PackageController::class, 'redeemMembershipShake'])->name('redeemShake');
});
// Fin paquetes


<?php

use App\Http\Controllers\Api\MembershipController;
use Illuminate\Support\Facades\Route;


// Membresías
Route::prefix('memberships')->name('memberships.')->middleware('auth:sanctum')->group(function () {
    Route::post('/my-memberships', [MembershipController::class, 'getMyMemberships'])->name('my-memberships');
    Route::post('/check-status', [MembershipController::class, 'checkMembershipStatus'])->name('check-status');
    Route::post('/summary', [MembershipController::class, 'getMembershipSummary'])->name('summary');
});
// Fin membresías

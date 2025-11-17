<?php

use App\Http\Controllers\Api\AuthRedController;
use Illuminate\Support\Facades\Route;


// Socials
Route::prefix('auth')->name('social-login.')->group(function () {
    // Facebook
    Route::get('facebook/redirect', [AuthRedController::class, 'facebookRedirect']);
    Route::get('facebook/callback', [AuthRedController::class, 'facebookCallback']);
    Route::post('facebook/token', [AuthRedController::class, 'facebookTokenLogin']);
    Route::get('facebook/url', [AuthRedController::class, 'getFacebookAuthUrl']);
    // Fin facebook

});

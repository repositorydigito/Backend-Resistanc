<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RecoverPasswordController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->name('auth.')->group(function () {
    // Registrar un nuevo cliente desde el app
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    // Login desde el app solo verifica clientes
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Email verification routes
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');

    // Password recovery routes
    Route::post('/send-reset-code', [RecoverPasswordController::class, 'sendResetCode'])->name('send-reset-code'); // envia codigo de actualizacion de contraseña al correo
    Route::post('/verify-reset-code', [RecoverPasswordController::class, 'verifyResetCode'])->name('verify-reset-code');
    Route::post('/reset-password', [RecoverPasswordController::class, 'resetPassword'])->name('reset-password');


    // Auth
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/me/update', [AuthController::class, 'updateMe'])->name('me.update');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('/password/update', [AuthController::class, 'updatePassword'])->name('password-update'); // Asegura que el usuario esté autenticado
    });
});

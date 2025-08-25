<?php

use App\Http\Controllers\Client\HomeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Ruta de prueba simple
Route::get('/test', function () {
    return 'Ruta de prueba funcionando';
});

// Rutas de verificación de email para clientes (completamente públicas)
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
    $user = \App\Models\User::find($id);

    if (!$user) {
        return view('auth.verification-error', ['message' => 'Usuario no encontrado.']);
    }

    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return view('auth.verification-error', ['message' => 'Enlace de verificación inválido.']);
    }

    if ($user->hasVerifiedEmail()) {
        return view('auth.verification-success', ['message' => 'Tu email ya ha sido verificado anteriormente.']);
    }

    $user->markEmailAsVerified();

    return view('auth.verification-success', ['message' => '¡Tu email ha sido verificado correctamente!']);
})->name('verification.verify');

// Ruta para reenviar email de verificación (pública, pero con throttling)
Route::post('/email/verification-notification', function () {
    // Obtener el email del formulario
    $email = request('email');

    if (!$email) {
        return back()->with('error', 'Email requerido.');
    }

    // Buscar el usuario por email
    $user = \App\Models\User::where('email', $email)->first();

    if (!$user) {
        return back()->with('error', 'No se encontró un usuario con ese email.');
    }

    if ($user->hasVerifiedEmail()) {
        return back()->with('info', 'Tu email ya ha sido verificado.');
    }

    // Enviar email de verificación
    $user->sendEmailVerificationNotification();

    return back()->with('success', 'Email de verificación enviado.');
})->name('verification.send');


Route::get('webhook/github', [\App\Http\Controllers\WebHook::class, 'github']);
Route::post('webhook/github', [\App\Http\Controllers\WebHook::class, 'github']);

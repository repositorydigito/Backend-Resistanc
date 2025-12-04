<?php

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\PackageController;
use App\Http\Controllers\Client\PrivacityController;
use App\Http\Controllers\Client\TestSunarController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/privacity', [PrivacityController::class, 'privacy'])->name('privacity');
Route::get('/terms', [PrivacityController::class, 'terms'])->name('term');
Route::get('/packages', [PackageController::class, 'index'])->name('package');

// Ruta de prueba simple
Route::get('/test', function () {
    return 'Ruta de prueba funcionando';
});

// Rutas de prueba para Sunat
Route::get('/test/sunat/boleta-mensual', [TestSunarController::class, 'testBoletaMensual'])->name('test.sunat.boleta-mensual');
Route::get('/test/sunat/factura', [TestSunarController::class, 'testFactura'])->name('test.sunat.factura');

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

    // Si ya está verificado, asegurar que tenga cliente de Stripe
    if ($user->hasVerifiedEmail()) {
        // Forzar verificación de cliente de Stripe incluso si ya está verificado
        $userObserver = new \App\Observers\UserObserver();
        $userObserver->ensureStripeCustomer($user);

        return view('auth.verification-success', ['message' => 'Tu email ya ha sido verificado anteriormente. Puedes ingresar al app sin complicaciones!']);
    }

    // Marcar como verificado (esto disparará el Observer)
    $user->markEmailAsVerified();

    return view('auth.verification-success', ['message' => '¡Tu email ha sido verificado correctamente. Puedes ingresar al app sin complicaciones!']);
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

<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Test route
Route::get('/test', [TestController::class, 'status'])->name('test.status');

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->name('auth.')->group(function () {
    // Public authentication routes
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    });
});

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('users')->name('users.')->group(function () {
    // Basic CRUD
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}', [UserController::class, 'update'])->name('patch');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

    // User relationships
    Route::get('/{user}/profile', [UserController::class, 'profile'])->name('profile');
    Route::get('/{user}/contacts', [UserController::class, 'contacts'])->name('contacts');
    Route::get('/{user}/social-accounts', [UserController::class, 'socialAccounts'])->name('social-accounts');
    Route::get('/{user}/login-audits', [UserController::class, 'loginAudits'])->name('login-audits');

    // User contacts CRUD
    Route::prefix('/{user}/contacts')->name('contacts.')->group(function () {
        Route::get('/', [UserContactController::class, 'index'])->name('index');
        Route::post('/', [UserContactController::class, 'store'])->name('store');
        Route::get('/{contact}', [UserContactController::class, 'show'])->name('show');
        Route::put('/{contact}', [UserContactController::class, 'update'])->name('update');
        Route::patch('/{contact}', [UserContactController::class, 'update'])->name('patch');
        Route::delete('/{contact}', [UserContactController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Future API Routes
|--------------------------------------------------------------------------
|
| TODO: Add routes for:
| - UserProfileController
| - UserContactController
| - SocialAccountController
| - LoginAuditController
|
*/

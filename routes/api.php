<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassScheduleController;
use App\Http\Controllers\Api\DisciplineController;
use App\Http\Controllers\Api\DrinkController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTagController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserContactController;
use App\Http\Controllers\Api\UserPackageController;
use App\Http\Controllers\Api\UserPayMethodController;
use App\Http\Controllers\Api\WaitingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StudioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Two\FacebookProvider;

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



// Logueo con redes sociales
// Socials
Route::prefix('social-login')->name('social-login.')->group(function () {
    // Facebook
    Route::get('/facebook', [AuthController::class, 'redirectToFacebook'])->name('facebook.redirect');
    Route::post('/social-login/facebook-token', [AuthController::class, 'loginWithFacebookToken']);
    // Fin facebook
    // Google
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::post('/social-login/google-token', [AuthController::class, 'loginWithGoogleToken']);
    // Fin google
});
// Fin socials
// Fin logueo con redes sociales


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

    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}', [UserController::class, 'update'])->name('patch');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

        // User relationships
        Route::get('/{user}/profile', [UserController::class, 'profile'])->name('profile');
        Route::get('/{user}/contacts', [UserController::class, 'contacts'])->name('contacts');
        Route::get('/{user}/social-accounts', [UserController::class, 'socialAccounts'])->name('social-accounts');
        Route::get('/{user}/login-audits', [UserController::class, 'loginAudits'])->name('login-audits');
    });


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
| User Payment Methods API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('me/payment-methods')->name('payment-methods.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserPayMethodController::class, 'index'])->name('index');
    Route::post('/', [UserPayMethodController::class, 'store'])->name('store');
    Route::get('/{paymentMethod}', [UserPayMethodController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| User Packages API Routes (Usuario Logueado)
|--------------------------------------------------------------------------
*/


Route::prefix('me/packages')->name('my-packages.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserPackageController::class, 'index'])->name('index');
    Route::post('/', [UserPackageController::class, 'store'])->name('store');
    Route::get('/summary-by-discipline', [UserPackageController::class, 'getPackagesSummaryByDiscipline'])->name('summary-by-discipline');
});


Route::prefix('packages')->name('packages.')->middleware('auth:sanctum')->group(function () {
    // Basic CRUD
    Route::get('/', [PackageController::class, 'index'])->name('index');
});


// Disciplinas
Route::prefix('disciplines')->name('disciplines.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [DisciplineController::class, 'index'])->name('index');
});
// Fin disciplinas

// Instructores
Route::prefix('instructors')->name('instructors.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [InstructorController::class, 'index'])->name('index');
    Route::get('/week', [InstructorController::class, 'instructorsWeek'])->name('week');
    Route::get('/show/{instructor}', [InstructorController::class, 'show'])->name('show');
    Route::post('/{instructor}/favorite', [InstructorController::class, 'scoreInstructor'])->name('favorite');
});
// Fin instructores

// Horarios
Route::prefix('class-schedules')->name('class-schedules.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ClassScheduleController::class, 'index'])->name('index');
    Route::get('/show/{classSchedule}', [ClassScheduleController::class, 'show'])->name('show');
    Route::get('/{classSchedule}/seat-map', [ClassScheduleController::class, 'getSeatMap'])->name('seat-map');
    Route::get('/{classSchedule}/check-packages', [ClassScheduleController::class, 'checkPackageAvailability'])->name('check-packages');
    Route::post('/{classSchedule}/reserve-seats', [ClassScheduleController::class, 'reserveSeats'])->name('reserve-seats');
    Route::post('/release-seats', [ClassScheduleController::class, 'releaseSeats'])->name('release-seats');
    Route::post('/confirm-attendance', [ClassScheduleController::class, 'confirmAttendance'])->name('confirm-attendance');
    Route::get('/my-reservations', [ClassScheduleController::class, 'getMyReservations'])->name('my-reservations');

});
// Fin Horarios

// Lista de espera
Route::prefix('waiting-list')->name('waiting-list.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [WaitingController::class, 'addWaitingList'])->name('add');
});
// Fin lista de espera

// Bebidas
Route::prefix('drinks')->name('drinks.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [DrinkController::class, 'index'])->name('index');
    Route::get('/{id}', [DrinkController::class, 'show'])->name('show');
});
// Fin bebidas


// Tienda
// Categorias de productos
Route::prefix('product-categories')->name('product-categories.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProductCategoryController::class, 'index'])->name('index');
});
// Fin categorias de productos

// Etiquetas de productos
Route::prefix('product-tags')->name('product-tags.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProductTagController::class, 'index'])->name('index');
});
// Fin etiquetas de productos

// Productos
Route::prefix('products')->name('products.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    // Route::post('/{product}/favorite', [ProductController::class, 'scoreProduct'])->name('favorite');
    // Route::post('/{product}/add-to-cart', [ProductController::class, 'addToCart'])->name('add-to-cart');
});
// Fin productos
// Carrito de compras
// Fin Tienda


// Favoritos
Route::prefix('favorites')->name('favorites.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [FavoriteController::class, 'index'])->name('index');
    Route::post('/drinks/{drink}', [FavoriteController::class, 'storeDrink'])->name('favorite-drink');
    Route::post('/products/{product}', [FavoriteController::class, 'storeProduct'])->name('favorite-product');
    Route::post('/classes/{class}', [FavoriteController::class, 'storeClass'])->name('favorite-class');
    Route::post('/instructors/{instructor}', [FavoriteController::class, 'storeInstructor'])->name('favorite-instructor');

});
// Fin Favoritos

// Home
Route::prefix('home')->name('home.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');
});
// Fin home

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

Route::apiResource('instructors', InstructorController::class);
Route::get('instructors-week', [InstructorController::class, 'instructorsWeek']);
Route::post('instructors/{instructor}/score', [InstructorController::class, 'scoreInstructor']);
Route::get('instructors-ten', [InstructorController::class, 'indexTen']);


// Rutas de Pedidos

Route::prefix('orders')->name('orders.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/', [OrderController::class, 'store'])->name('store');
});





<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClassScheduleController;
use App\Http\Controllers\Api\DisciplineController;
use App\Http\Controllers\Api\DrinkController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\FootwearController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTagController;
use App\Http\Controllers\Api\RecoverPasswordController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;

use App\Http\Controllers\Api\UserPackageController;
use App\Http\Controllers\Api\UserPayMethodController;
use App\Http\Controllers\Api\WaitingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasarelaController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ShoppingCartController;
use App\Http\Controllers\Api\StudioController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\JuiceCartCodeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProductVariantApiController;
use App\Http\Controllers\Api\TagController;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Two\FacebookProvider;

// Ruta de testeo
Route::post('/test', [TestController::class, 'status'])->name('test.status');

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

Route::prefix('auth')->name('auth.')->group(function () {
    // Public authentication routes
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Email verification routes
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');

    // Password recovery routes
    Route::post('/send-reset-code', [RecoverPasswordController::class, 'sendResetCode'])->name('send-reset-code');
    Route::post('/verify-reset-code', [RecoverPasswordController::class, 'verifyResetCode'])->name('verify-reset-code');
    Route::post('/reset-password', [RecoverPasswordController::class, 'resetPassword'])->name('reset-password');


    // Auth
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/me/update', [AuthController::class, 'updateMe'])->name('me.update');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    });
});

// Home
Route::prefix('home')->name('home.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');
});
// Fin home

// Paquetes
Route::prefix('packages')->name('packages.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [PackageController::class, 'index'])->name('index');
    Route::post('/show', [PackageController::class, 'show'])->name('show');
    Route::post('/me', [PackageController::class, 'packageMe'])->name('me');
    Route::post('/me/create', [PackageController::class, 'packageMeCreate'])->name('meCreate');
});

// Disciplinas
Route::prefix('disciplines')->name('disciplines.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [DisciplineController::class, 'index'])->name('index');
});
// Fin disciplinas

// Instructores
Route::prefix('instructors')->name('instructors.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [InstructorController::class, 'index'])->name('index');
    Route::post('/week', [InstructorController::class, 'instructorsWeek'])->name('week');
    Route::post('/show', [InstructorController::class, 'show'])->name('show');
    Route::post('/rate', [InstructorController::class, 'scoreInstructor'])->name('favorite');
    Route::post('ten', [InstructorController::class, 'indexTen']);
});
// Fin instructores

// Artículos
Route::prefix('posts')->name('posts.')->middleware('auth:sanctum')->group(function () {
    Route::post('/list', [PostController::class, 'index'])->name('index');
    Route::post('/show', [PostController::class, 'show'])->name('show');
    Route::post('/category/list', [PostController::class, 'categories'])->name('categories');
    Route::post('/tags/list', [PostController::class, 'tags'])->name('tags');
});
// Fin articulos

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

// Lista de espera
Route::prefix('waiting-list')->name('waiting-list.')->middleware('auth:sanctum')->group(function () {
    Route::post('/list', [WaitingController::class, 'indexWaitingList'])->name('index');
    Route::post('create/', [WaitingController::class, 'addWaitingList'])->name('add');
    Route::post('/show', [WaitingController::class, 'show'])->name('show');
    Route::post('/check-status', [WaitingController::class, 'checkWaitingStatus'])->name('check-status');
    Route::post('/delete', [WaitingController::class, 'destroy'])->name('destroy');
});
// Fin lista de espera

// Bebidas
Route::prefix('drinks')->name('drinks.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [DrinkController::class, 'index'])->name('index');
    Route::post('/show', [DrinkController::class, 'show'])->name('show');
    Route::post('/base-drinks', [DrinkController::class, 'baseDrinks'])->name('base-drinks');
    Route::post('/flavor-drinks', [DrinkController::class, 'flavorDrinks'])->name('flavor-drinks');
    Route::post('/type-drinks', [DrinkController::class, 'typeDrinks'])->name('type-drinks');
    // Carrito shakes
    Route::post('/cart/add', [DrinkController::class, 'addToCart'])->name('add-to-cart');
    Route::post('/cart/show', [DrinkController::class, 'showToCart'])->name('show-to-cart');
    Route::post('/cart/remove', [DrinkController::class, 'removeFromCart'])->name('remove-from-cart');
    Route::post('/cart/update-quantity', [DrinkController::class, 'updateCartQuantity'])->name('update-quantity');
});
// Fin bebidas


// Tienda
// Productos
Route::prefix('products')->name('products.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ProductController::class, 'index'])->name('index');
    Route::post('/show', [ProductController::class, 'show'])->name('show');
    Route::post('/categories/list', [ProductController::class, 'categories'])->name('categories');
    Route::post('/tags/list', [ProductController::class, 'tags'])->name('tags');
});
// Fin productos
// Fin Tienda


// Favoritos
Route::prefix('favorites')->name('favorites.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [FavoriteController::class, 'index'])->name('index');
    Route::post('/drinks/add', [FavoriteController::class, 'storeDrink'])->name('favorite-drink-add');
    Route::post('/products', [FavoriteController::class, 'products'])->name('favorite-products');
    Route::post('/products/add', [FavoriteController::class, 'storeProduct'])->name('favorite-product-add');
    Route::post('/classes/add', [FavoriteController::class, 'storeClass'])->name('favorite-class-add');
    Route::post('/instructors/add', [FavoriteController::class, 'storeInstructor'])->name('favorite-instructor-add');
});
// Fin Favoritos


// Tarjetas
Route::prefix('me/cards')->name('cards.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/create', [PaymentController::class, 'store'])->name('store');
    Route::get('/show/{card}', [PaymentController::class, 'show'])->name('show');
    Route::put('/update/{card}', [PaymentController::class, 'update'])->name('update');
    Route::delete('/destroy/{card}', [PaymentController::class, 'destroy'])->name('destroy');
    Route::post('/select/{card}', [PaymentController::class, 'selectPayment'])->name('select');
    Route::post('/default', [PaymentController::class, 'defaultPayment'])->name('default');
});

// Fin tarjetas


// Carrito de compras
Route::prefix('shopping-cart')->name('shopping-cart.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ShoppingCartController::class, 'show'])->name('show');
    Route::post('/add', [ShoppingCartController::class, 'add'])->name('add');
    Route::post('/remove', [ShoppingCartController::class, 'remove'])->name('remove');
    Route::put('/update-quantity', [ShoppingCartController::class, 'updateQuantity'])->name('update-quantity');
    Route::delete('/clear', [ShoppingCartController::class, 'clear'])->name('clear');
    Route::post('/confirm', [ShoppingCartController::class, 'confirm'])->name('confirm');
});
// Fin carrito



// Fin carrito shake


// Rutas de Pedidos
Route::prefix('orders')->name('orders.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [OrderController::class, 'show'])->name('show');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/', [OrderController::class, 'store'])->name('store');
});
// Fin rutas de Pedidos


// Pasarela de pago
Route::prefix('payment-gateway')->name('payment-gateway.')->middleware('auth:sanctum')->group(function () {
    Route::post('/izipay/token', [PasarelaController::class, 'izipayToken'])->name('izipay.token');
});
// Fin Pasarela de pago

// Facturación
Route::prefix('invoices')->name('invoices.')->middleware('auth:sanctum')->group(function () {
    Route::post('/generate', [InvoiceController::class, 'generarComprobante'])->name('generate');
});


// Reservas de calzado
Route::prefix('footwear')->name('footwear.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [FootwearController::class, 'index'])->name('index');
    Route::post('/class-schedule', [FootwearController::class, 'indexClassSchedule'])->name('index-class-schedule');
    Route::post('/reserve', [FootwearController::class, 'reserve'])->name('reserve');
});

<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\StoreOrderController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



// Ruta de testeo
Route::post('/test', [TestController::class, 'status'])->name('test.status');

// Home
Route::prefix('home')->name('home.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');
});

// Empresa
Route::prefix('company')->name('company.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [CompanyController::class, 'show'])->name('show');
});

// Historial
Route::prefix('history')->name('history.')->middleware('auth:sanctum')->group(function () {
    Route::post('/classes', [HistoryController::class, 'getClassHistory'])->name('classes');
});

// Rutas de Pedidos Unificados (Shakes + Productos)
Route::prefix('store-orders')->name('store-orders.')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [StoreOrderController::class, 'index'])->name('index');
});

// Facturación
Route::prefix('invoices')->name('invoices.')->middleware('auth:sanctum')->group(function () {
    Route::post('/generate', [InvoiceController::class, 'generarComprobante'])->name('generate');
});



// Apis de auth login y registro
require __DIR__ . '/apisapp/auth/api-auth.php';

// Apis de auth de redes facebook


// Apis de carrito de compras de productos
require __DIR__ . '/apisapp/api-paquetes.php';

// Apis de codigos promocionales
require __DIR__ . '/apisapp/api-codigospromocionales.php';

// Apis de disciplinas
require __DIR__ . '/apisapp/api-disciplinas.php';

// Apis de instructores
require __DIR__ . '/apisapp/api-instructores.php';

// Apis de articulos
require __DIR__ . '/apisapp/api-articulos.php';

// Apis de horarios
require __DIR__ . '/apisapp/api-horarios.php';

// Apis de lista de espera
require __DIR__ . '/apisapp/api-listaespera.php';

// Apis de bebidas y carrito
require __DIR__ . '/apisapp/shake/api-bebidas.php';

// Apis de ordenes de bebidas
require __DIR__ . '/apisapp/shake/api-ordenes.php';

// Apis de productos
require __DIR__ . '/apisapp/tienda/api-productos.php';

// Apis de favoritos
require __DIR__ . '/apisapp/api-favoritos.php';

// Apis de carrito de compras de productos
require __DIR__ . '/apisapp/tienda/api-carrito.php';

// Apis de ordenes de productos
require __DIR__ . '/apisapp/tienda/api-ordenes.php';

// Apis de membresia
require __DIR__ . '/apisapp/api-membresia.php';

// Apis de calzado
require __DIR__ . '/apisapp/api-calzado.php';

// Apis de tarjetas
require __DIR__ . '/apisapp/pasarela/api-tarjetas.php';

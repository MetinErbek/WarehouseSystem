<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\WarehousesController;
use App\Http\Controllers\WarehouseProductsController;
use App\Http\Controllers\OrdersController;
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


Route::resource('products', ProductsController::class);
Route::resource('warehouses', WarehousesController::class);
Route::resource('orders', OrdersController::class);
Route::post('setwarehousestock', [WarehouseProductsController::class, 'setWarehouseStock']);

Route::fallback(function () {

    return jsonResponse(FALSE, 'Route not found !', NULL, 404);

});
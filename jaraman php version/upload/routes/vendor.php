<?php

use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\Api\VendorDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
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

Route::prefix('jaram/vendor')->middleware('verified', 'auth:sanctum')->group(function () {
    Route::middleware('vendor')->group(function () {   
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('vendor.dashboard');     
        Route::controller(OrderController::class)->prefix('orders')->group(function () {
            Route::get('/', 'getAvailableOrders');
            Route::get('/accepted', 'myOrders');
            Route::get('/{id}', 'showOrderByItemId');
            Route::post('/item/{id}/decision', 'decide');
        });


    });
});


<?php

use App\Http\Controllers\Api\Storefront\CartController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]))->name('health');

Route::post('/carts', [CartController::class, 'store'])->name('carts.store');
Route::get('/carts/{cartId}', [CartController::class, 'show'])->whereNumber('cartId')->name('carts.show');
Route::post('/carts/{cartId}/lines', [CartController::class, 'addLine'])->whereNumber('cartId')->name('carts.lines.store');
Route::put('/carts/{cartId}/lines/{lineId}', [CartController::class, 'updateLine'])->whereNumber(['cartId', 'lineId'])->name('carts.lines.update');
Route::delete('/carts/{cartId}/lines/{lineId}', [CartController::class, 'removeLine'])->whereNumber(['cartId', 'lineId'])->name('carts.lines.destroy');

<?php

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/products', fn () => Product::query()->paginate());

    Route::get('/orders', fn () => Order::query()->paginate());
});

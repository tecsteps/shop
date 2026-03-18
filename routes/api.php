<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin/v1/stores/{store}')->group(function () {
    Route::get('products', function (Request $request, int $store) {
        if (! $request->user()->tokenCan('read-products')) {
            abort(403);
        }

        return response()->json(['data' => []]);
    });

    Route::post('products', function (Request $request, int $store) {
        if (! $request->user()->tokenCan('write-products')) {
            abort(403);
        }

        return response()->json(['data' => []], 201);
    });
});

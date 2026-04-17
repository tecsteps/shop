<?php

use Illuminate\Support\Facades\Route;

// Routes defined here are registered under the /api prefix.
// Phase 11a backend teammate is expanding this file with Sanctum + resources.
Route::middleware('auth:sanctum')->get('/user', fn ($request) => $request->user());

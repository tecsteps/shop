<?php

use Illuminate\Support\Facades\Route;

// Cart API routes are defined here once Phase 4 lands. For now, provide a minimal
// health endpoint so the API router boots cleanly and we can add routes incrementally.
Route::get('/health', fn () => response()->json(['ok' => true]))->name('health');

<?php

use Illuminate\Support\Facades\Route;

// Admin API routes grow as phases land. Health endpoint confirms auth + rate-limit work.
Route::get('/health', fn () => response()->json(['ok' => true]))->name('health');

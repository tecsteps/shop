<?php

use Illuminate\Support\Facades\Route;

Route::view('/dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

require __DIR__.'/storefront.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';

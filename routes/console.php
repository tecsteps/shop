<?php

use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire stale checkouts (spec 05 §6.2) and abandon inactive carts (spec 05 §4.5).
Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes();
Schedule::job(new CleanupAbandonedCarts)->daily();

<?php

use App\Jobs\ExpireAbandonedCheckouts;
use App\Services\CheckoutService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(static function (): void {
    app(ExpireAbandonedCheckouts::class)->handle(app(CheckoutService::class));
})->name('expire-abandoned-checkouts')->everyFifteenMinutes();

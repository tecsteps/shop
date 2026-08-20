<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new \App\Jobs\CleanupAbandonedCarts)->daily();
Schedule::job(new \App\Jobs\ExpireAbandonedCheckouts)->everyFifteenMinutes();
Schedule::job(new \App\Jobs\AggregateAnalytics)->dailyAt('01:00');
Schedule::job(new \App\Jobs\CancelUnpaidBankTransferOrders)->dailyAt('02:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

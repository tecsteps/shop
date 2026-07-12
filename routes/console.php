<?php

use App\Jobs\AggregateAnalytics;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes()->withoutOverlapping();
Schedule::job(new CleanupAbandonedCarts)->dailyAt('00:30')->withoutOverlapping();
Schedule::job(new AggregateAnalytics)->dailyAt('01:00')->withoutOverlapping();
Schedule::job(new CancelUnpaidBankTransferOrders)->dailyAt('02:00')->withoutOverlapping();

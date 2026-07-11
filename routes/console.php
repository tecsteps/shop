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

Schedule::job(new AggregateAnalytics)
    ->dailyAt('01:00')
    ->timezone('UTC')
    ->withoutOverlapping();

Schedule::job(new ExpireAbandonedCheckouts)
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::job(new CleanupAbandonedCarts)
    ->daily()
    ->withoutOverlapping();

Schedule::job(new CancelUnpaidBankTransferOrders)
    ->daily()
    ->withoutOverlapping();

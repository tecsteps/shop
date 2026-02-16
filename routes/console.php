<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new \App\Jobs\ExpireAbandonedCheckouts)->hourly();
Schedule::job(new \App\Jobs\CleanupAbandonedCarts)->daily();
Schedule::job(new \App\Jobs\CancelUnpaidBankTransferOrders)->daily();
Schedule::job(new \App\Jobs\AggregateAnalytics)->daily();

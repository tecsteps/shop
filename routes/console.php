<?php

use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(ExpireAbandonedCheckouts::class)->everyFifteenMinutes();
Schedule::job(CleanupAbandonedCarts::class)->daily();
Schedule::job(CancelUnpaidBankTransferOrders::class)->daily();

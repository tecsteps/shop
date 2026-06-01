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

/*
|--------------------------------------------------------------------------
| Commerce Maintenance Schedule
|--------------------------------------------------------------------------
|
| Expire idle checkouts (releasing reserved inventory), mark stale carts as
| abandoned, and cancel unpaid bank-transfer orders past their window.
|
*/

Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes();
Schedule::job(new CleanupAbandonedCarts)->daily();
Schedule::job(new CancelUnpaidBankTransferOrders)->daily();

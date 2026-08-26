<?php

use App\Jobs\AggregateAnalytics;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new CleanupAbandonedCarts)->daily();
Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes();
Schedule::job(new AggregateAnalytics)->dailyAt('01:00');
Schedule::job(new CancelUnpaidBankTransferOrders)->daily();

<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

final class OutboundDispatcher
{
    public function afterCommit(Closure $callback): void
    {
        $safeCallback = static function () use ($callback): void {
            try {
                $callback();
            } catch (Throwable $exception) {
                report($exception);
            }
        };

        // RefreshDatabase owns level one during tests; it is not an application transaction.
        if (app()->runningUnitTests() && DB::transactionLevel() <= 1) {
            $safeCallback();

            return;
        }

        DB::afterCommit($safeCallback);
    }
}

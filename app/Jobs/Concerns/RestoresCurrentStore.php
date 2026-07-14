<?php

namespace App\Jobs\Concerns;

use Closure;

trait RestoresCurrentStore
{
    protected function restoringCurrentStore(Closure $callback): mixed
    {
        $hadStore = app()->bound('current_store');
        $previousStore = $hadStore ? app('current_store') : null;

        try {
            return $callback();
        } finally {
            if ($hadStore) {
                app()->instance('current_store', $previousStore);
            } else {
                app()->forgetInstance('current_store');
            }
        }
    }
}

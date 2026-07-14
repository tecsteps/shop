<?php

namespace App\Observers;

use App\Models\StoreDomain;
use Illuminate\Support\Facades\Cache;

final class StoreDomainObserver
{
    public function saved(StoreDomain $domain): void
    {
        Cache::forget('store_domain:'.mb_strtolower((string) $domain->hostname));
        $original = $domain->getRawOriginal('hostname');
        if (is_string($original) && $original !== $domain->hostname) {
            Cache::forget('store_domain:'.mb_strtolower($original));
        }
    }

    public function deleted(StoreDomain $domain): void
    {
        Cache::forget('store_domain:'.mb_strtolower((string) $domain->hostname));
    }
}

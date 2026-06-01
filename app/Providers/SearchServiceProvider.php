<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Wires search-index maintenance: registers {@see ProductObserver} so the
 * FTS5 index stays in sync as products are created, updated, and deleted.
 */
class SearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
    }
}

<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ProductDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /** @param array<string, mixed> $product */
    public function __construct(public readonly int $storeId, public readonly array $product) {}
}

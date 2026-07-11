<?php

namespace App\Events;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly ProductStatus $previousStatus,
        public readonly ProductStatus $newStatus,
    ) {}
}

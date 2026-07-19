<?php

namespace App\Events;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;

class ProductStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Product $product,
        public ProductStatus $oldStatus,
        public ProductStatus $newStatus,
    ) {}
}

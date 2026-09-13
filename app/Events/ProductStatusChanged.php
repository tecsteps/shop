<?php

namespace App\Events;

use App\Enums\ProductStatus;
use App\Models\Product;

class ProductStatusChanged
{
    public function __construct(
        public Product $product,
        public ProductStatus $from,
        public ProductStatus $to,
    ) {}
}

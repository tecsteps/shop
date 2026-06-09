<?php

namespace App\Events;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public ProductStatus $previousStatus,
        public ProductStatus $newStatus,
    ) {}
}

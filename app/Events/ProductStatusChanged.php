<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly string $from,
        public readonly string $to,
    ) {}
}

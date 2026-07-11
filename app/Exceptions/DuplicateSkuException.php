<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateSkuException extends RuntimeException
{
    public function __construct(public readonly string $sku)
    {
        parent::__construct("The SKU [{$sku}] is already used by another variant in this store.");
    }

    /** @return array<string, string> */
    public function context(): array
    {
        return ['sku' => $this->sku];
    }
}

<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\ShippingRateType;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int|string>
 */
final readonly class ShippingRateQuote implements Arrayable
{
    public function __construct(
        public int $rateId,
        public string $name,
        public ShippingRateType $type,
        public int $amount,
    ) {}

    /**
     * @return array{id: int, name: string, type: string, amount: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->rateId,
            'name' => $this->name,
            'type' => $this->type->value,
            'amount' => $this->amount,
        ];
    }
}

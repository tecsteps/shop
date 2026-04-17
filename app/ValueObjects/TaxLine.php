<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int|string>
 */
final readonly class TaxLine implements Arrayable
{
    public function __construct(
        public string $name,
        public int $rate,
        public int $amount,
    ) {}

    /**
     * @return array{name: string, rate: int, amount: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'rate' => $this->rate,
            'amount' => $this->amount,
        ];
    }
}

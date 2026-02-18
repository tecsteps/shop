<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $variant_id
 * @property int $quantity
 * @property int $unit_price_amount
 * @property int $line_subtotal_amount
 * @property int $line_discount_amount
 * @property int $line_total_amount
 * @property array<string, mixed>|null $properties
 */
class CartLine extends Model
{
    /** @use HasFactory<\Database\Factories\CartLineFactory> */
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'variant_id',
        'quantity',
        'unit_price_amount',
        'line_subtotal_amount',
        'line_discount_amount',
        'line_total_amount',
        'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_amount' => 'integer',
            'line_subtotal_amount' => 'integer',
            'line_discount_amount' => 'integer',
            'line_total_amount' => 'integer',
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function recalculateAmounts(): void
    {
        $this->line_subtotal_amount = $this->unit_price_amount * $this->quantity;
        $this->line_total_amount = $this->line_subtotal_amount - $this->line_discount_amount;
    }
}

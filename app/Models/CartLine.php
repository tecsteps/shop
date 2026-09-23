<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['cart_id', 'variant_id', 'quantity', 'unit_price_amount', 'line_subtotal_amount', 'line_discount_amount', 'line_total_amount'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->variant?->product?->title);
    }

    protected function product(): Attribute
    {
        return Attribute::get(fn (): ?Product => $this->variant?->product);
    }

    protected function variantTitle(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->variant?->title);
    }

    protected function priceAmount(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->unit_price_amount);
    }
}

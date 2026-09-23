<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'customer_id', 'currency', 'cart_version', 'status', 'discount_code'];

    protected function subtotalAmount(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->lines->sum('line_subtotal_amount'));
    }

    protected function discountAmount(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->lines->sum('line_discount_amount'));
    }

    protected function totalAmount(): Attribute
    {
        return Attribute::get(fn (): int => max(0, $this->subtotal_amount - $this->discount_amount));
    }

    protected function itemCount(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->lines->sum('quantity'));
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

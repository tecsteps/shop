<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'customer_id', 'currency', 'cart_version', 'status', 'discount_code'];

    protected function casts(): array
    {
        return ['status' => CartStatus::class];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    public function subtotalAmount(): int
    {
        return (int) $this->lines->sum('line_total_amount');
    }

    public function itemCount(): int
    {
        return (int) $this->lines->sum('quantity');
    }
}

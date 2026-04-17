<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'customer_id', 'currency', 'cart_version', 'status'];

    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    public function subtotal(): int
    {
        return (int) $this->lines->sum('line_subtotal_amount');
    }

    public function lineCount(): int
    {
        return (int) $this->lines->sum('quantity');
    }
}

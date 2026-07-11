<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'provider', 'method', 'provider_payment_id', 'status', 'amount', 'currency', 'raw_json_encrypted'];

    protected $hidden = ['raw_json_encrypted'];

    protected $attributes = ['provider' => 'mock', 'status' => 'pending', 'amount' => 0, 'currency' => 'USD'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    protected function casts(): array
    {
        return ['method' => PaymentMethod::class, 'status' => PaymentStatus::class, 'raw_json_encrypted' => 'encrypted:array'];
    }
}

<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'provider', 'provider_payment_id', 'method', 'status', 'amount', 'raw_json_encrypted'];

    protected $hidden = ['raw_json_encrypted'];

    protected function casts(): array
    {
        return ['method' => PaymentMethod::class, 'status' => PaymentStatus::class, 'raw_json_encrypted' => 'encrypted'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

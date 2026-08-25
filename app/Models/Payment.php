<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'method',
        'provider_payment_id',
        'status',
        'amount',
        'currency',
        'raw_json_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'raw_json_encrypted' => 'encrypted:array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

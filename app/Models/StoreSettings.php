<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingsFactory> */
    use HasFactory;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'store_name',
        'store_email',
        'timezone',
        'weight_unit',
        'currency',
        'checkout_requires_account',
    ];

    protected function casts(): array
    {
        return [
            'checkout_requires_account' => 'boolean',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

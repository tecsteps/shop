<?php

namespace App\Models;

use App\Enums\StoreDomainType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    /** @use HasFactory<\Database\Factories\StoreDomainFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'hostname',
        'type',
        'is_primary',
        'tls_mode',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => false,
        'tls_mode' => 'managed',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StoreDomainType::class,
            'is_primary' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

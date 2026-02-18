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

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'hostname',
        'type',
        'is_primary',
        'tls_mode',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StoreDomainType::class,
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
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

<?php

namespace App\Models;

use App\Enums\StoreDomainType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['store_id', 'hostname', 'type', 'is_primary', 'tls_mode'];

    protected function casts(): array
    {
        return [
            'type' => StoreDomainType::class,
            'is_primary' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}


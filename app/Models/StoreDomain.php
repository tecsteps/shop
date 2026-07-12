<?php

namespace App\Models;

use App\Enums\StoreDomainType;
use App\Enums\TlsMode;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreDomain extends Model
{
    use BelongsToStore, HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['store_id', 'hostname', 'type', 'is_primary', 'tls_mode'];

    protected function casts(): array
    {
        return [
            'type' => StoreDomainType::class,
            'is_primary' => 'boolean',
            'tls_mode' => TlsMode::class,
        ];
    }
}

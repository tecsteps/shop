<?php

namespace App\Models;

use App\Enums\StoreDomainTlsMode;
use App\Enums\StoreDomainType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'hostname',
        'type',
        'is_primary',
        'tls_mode',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => StoreDomainType::class,
            'tls_mode' => StoreDomainTlsMode::class,
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

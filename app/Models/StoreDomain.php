<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDomain extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['store_id', 'hostname', 'type', 'is_primary', 'tls_mode', 'created_at'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'created_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

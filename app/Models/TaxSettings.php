<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    /** @use HasFactory<\Database\Factories\TaxSettingsFactory> */
    use HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'store_id';

    protected $fillable = ['store_id', 'mode', 'provider', 'prices_include_tax', 'config_json'];

    protected $attributes = ['mode' => 'manual', 'provider' => 'none', 'prices_include_tax' => false, 'config_json' => '{}'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected function casts(): array
    {
        return ['mode' => TaxMode::class, 'prices_include_tax' => 'boolean', 'config_json' => 'array'];
    }
}

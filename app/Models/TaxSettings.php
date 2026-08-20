<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    use BelongsToStore;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = ['store_id', 'mode', 'provider', 'prices_include_tax', 'config_json', 'default_rate_basis_points', 'rates_json', 'provider_config_json'];

    protected function casts(): array
    {
        return ['rates_json' => 'array', 'provider_config_json' => 'array', 'config_json' => 'array', 'prices_include_tax' => 'boolean'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

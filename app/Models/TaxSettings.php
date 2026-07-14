<?php

namespace App\Models;

use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSettings extends Model
{
    use BelongsToStore, HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'store_id';

    protected $fillable = ['store_id', 'mode', 'provider', 'prices_include_tax', 'config_json'];

    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'provider' => TaxProviderType::class,
            'prices_include_tax' => 'boolean',
            'config_json' => 'array',
        ];
    }
}

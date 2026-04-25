<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSettings extends Model
{
    public $incrementing = false;

    public const CREATED_AT = null;

    protected $primaryKey = 'store_id';

    protected $fillable = ['store_id', 'prices_include_tax', 'default_rate_bps'];

    protected function casts(): array
    {
        return ['prices_include_tax' => 'boolean'];
    }
}


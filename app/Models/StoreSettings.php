<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    public $incrementing = false;

    public const CREATED_AT = null;

    protected $primaryKey = 'store_id';

    protected $fillable = ['store_id', 'settings_json'];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}


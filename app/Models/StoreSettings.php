<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    use HasFactory;

    /** @var string */
    protected $primaryKey = 'store_id';

    /** @var bool */
    public $incrementing = false;

    const CREATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'settings_json',
    ];

    /** @return array<string, string> */
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

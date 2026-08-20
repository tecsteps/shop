<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSettings extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingsFactory> */
    use BelongsToStore, HasFactory;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['store_id', 'settings_json', 'general_json', 'checkout_json', 'notification_json', 'social_json'];

    protected $attributes = [
        'settings_json' => '{}',
    ];

    protected function casts(): array
    {
        return ['settings_json' => 'array', 'general_json' => 'array', 'checkout_json' => 'array', 'notification_json' => 'array', 'social_json' => 'array'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

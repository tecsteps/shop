<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'event', 'target_url', 'secret_encrypted', 'status', 'consecutive_failures'];

    protected $hidden = ['secret_encrypted'];

    protected function casts(): array
    {
        return ['secret_encrypted' => 'encrypted'];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}

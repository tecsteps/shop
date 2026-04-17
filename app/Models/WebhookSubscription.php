<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSubscription extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'app_installation_id', 'topic', 'target_url', 'secret', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class);
    }
}

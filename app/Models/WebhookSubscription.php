<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSubscription extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'app_installation_id',
        'event_type',
        'target_url',
        'signing_secret_encrypted',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'signing_secret_encrypted' => 'encrypted',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'app_installation_id');
    }
}

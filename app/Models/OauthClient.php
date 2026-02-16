<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthClient extends Model
{
    /** @use HasFactory<\Database\Factories\OauthClientFactory> */
    use HasFactory;

    protected $fillable = [
        'app_id',
        'client_id',
        'client_secret',
        'redirect_uri',
    ];

    /** @return BelongsTo<AppModel, $this> */
    public function app(): BelongsTo
    {
        return $this->belongsTo(AppModel::class, 'app_id');
    }
}

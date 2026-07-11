<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthClient extends Model
{
    /** @use HasFactory<\Database\Factories\OauthClientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['app_id', 'client_id', 'client_secret_encrypted', 'redirect_uris_json'];

    protected $hidden = ['client_secret_encrypted'];

    protected $attributes = ['redirect_uris_json' => '[]'];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    protected function casts(): array
    {
        return ['client_secret_encrypted' => 'encrypted', 'redirect_uris_json' => 'array'];
    }
}

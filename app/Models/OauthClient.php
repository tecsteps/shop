<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $app_id
 * @property string $client_id
 * @property string $client_secret_encrypted
 * @property array<int, string> $redirect_uris_json
 * @property-read App $app
 */
class OauthClient extends Model
{
    /** @use HasFactory<\Database\Factories\OauthClientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'client_id',
        'client_secret_encrypted',
        'redirect_uris_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_secret_encrypted' => 'encrypted',
            'redirect_uris_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}

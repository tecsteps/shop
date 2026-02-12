<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppInstallation extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'app_id',
        'store_id',
        'status',
        'granted_scopes_json',
        'access_token_encrypted',
        'installed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'granted_scopes_json' => 'array',
            'installed_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

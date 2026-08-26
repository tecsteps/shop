<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppInstallation extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'app_id', 'scopes_json', 'status', 'installed_at'];

    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
            'installed_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}

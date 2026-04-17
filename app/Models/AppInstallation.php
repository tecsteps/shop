<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppInstallation extends Model
{
    use BelongsToStore;

    public $timestamps = false;

    protected $fillable = ['store_id', 'app_id', 'status', 'scopes_granted_json', 'installed_at'];

    protected function casts(): array
    {
        return [
            'scopes_granted_json' => 'array',
            'installed_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}

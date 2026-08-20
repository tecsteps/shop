<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppInstallation extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'app_id', 'status', 'config'];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}

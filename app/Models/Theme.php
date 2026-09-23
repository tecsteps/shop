<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'version', 'status', 'is_active', 'published_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'published_at' => 'datetime'];
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ThemeSetting::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'status', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ThemeSetting::class);
    }
}

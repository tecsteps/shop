<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSettings extends Model
{
    use BelongsToStore, HasFactory;

    public const CREATED_AT = null;

    public $incrementing = false;

    protected $primaryKey = 'store_id';

    protected $fillable = ['store_id', 'settings_json'];

    protected function casts(): array
    {
        return ['settings_json' => 'array'];
    }
}

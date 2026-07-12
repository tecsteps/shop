<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavigationItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['menu_id', 'type', 'label', 'url', 'resource_id', 'position'];

    protected function casts(): array
    {
        return [
            'type' => NavigationItemType::class,
            'resource_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'resource_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'resource_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'resource_id');
    }
}

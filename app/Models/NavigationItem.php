<?php

namespace App\Models;

use App\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'menu_id',
        'type',
        'label',
        'url',
        'resource_id',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NavigationItemType::class,
            'resource_id' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    public function resolveUrl(): string
    {
        return match ($this->type) {
            NavigationItemType::Link => (string) ($this->url ?? '#'),
            NavigationItemType::Page => '/pages/'.$this->resource_id,
            NavigationItemType::Collection => '/collections/'.$this->resource_id,
            NavigationItemType::Product => '/products/'.$this->resource_id,
        };
    }
}

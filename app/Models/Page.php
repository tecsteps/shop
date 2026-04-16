<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'title', 'handle', 'body_html', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published->value)
            ->whereNotNull('published_at');
    }
}

<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'title',
        'handle',
        'body_html',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * The route key is the URL-friendly handle, not the numeric id.
     */
    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    /**
     * Whether the page is published (publicly visible on the storefront).
     */
    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    /**
     * Scope a query to published pages only.
     *
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published->value);
    }
}

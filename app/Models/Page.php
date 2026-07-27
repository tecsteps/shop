<?php

namespace App\Models;

use App\Actions\SanitizeHtml;
use App\Enums\PageStatus;
use App\Models\Concerns\BelongsToStore;
use App\Support\HandleGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use BelongsToStore, HasFactory;

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
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        // Default the handle from the title, unique per store.
        static::creating(function (Page $page): void {
            if (blank($page->handle)) {
                $page->handle = HandleGenerator::generate($page->title, 'pages', $page->store_id);
            }
        });

        // Sanitize rich-text content against the HTML allowlist.
        static::saving(function (Page $page): void {
            $page->body_html = app(SanitizeHtml::class)($page->body_html);
        });
    }

    /**
     * Scope to pages visible on the storefront.
     *
     * @param  Builder<Page>  $query
     */
    protected function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published);
    }
}

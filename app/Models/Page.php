<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\BelongsToStore;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'title', 'handle', 'content', 'body_html', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['status' => PageStatus::class, 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if ($page->isDirty('body_html') && ! $page->isDirty('content')) {
                $page->content = $page->body_html;
            }
            $page->content = app(HtmlSanitizer::class)->sanitize($page->content);
            $page->body_html = $page->content;
        });
    }
}

<?php

namespace App\Models;

use App\Actions\SanitizeHtml;
use App\Enums\PageStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'title', 'handle', 'body_html', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function setBodyHtmlAttribute(mixed $value): void
    {
        $this->attributes['body_html'] = app(SanitizeHtml::class)->execute($value === null ? null : (string) $value);
    }
}

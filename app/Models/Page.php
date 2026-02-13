<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use BelongsToStore, HasFactory;

    /**
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }
}

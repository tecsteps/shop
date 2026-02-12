<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'billing_email',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}

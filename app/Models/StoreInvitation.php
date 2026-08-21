<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreInvitation extends Model
{
    /** @use HasFactory<\Database\Factories\StoreInvitationFactory> */
    use HasFactory;

    protected $fillable = ['store_id', 'email', 'role', 'invited_at', 'expires_at', 'accepted_at'];

    protected function casts(): array
    {
        return ['invited_at' => 'datetime', 'expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

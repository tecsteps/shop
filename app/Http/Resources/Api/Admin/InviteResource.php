<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StoreUser
 */
class InviteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->user->email,
            'role' => $this->role->value,
            'invited_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ];
    }
}

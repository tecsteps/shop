<?php

namespace App\Http\Resources\Api\Admin;

use App\Services\RolePermissionMap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\StoreUser
 */
class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'role' => $this->role->value,
            'email' => $this->user->email,
            'name' => $this->user->name,
            'permissions' => RolePermissionMap::for($this->role),
        ];
    }
}

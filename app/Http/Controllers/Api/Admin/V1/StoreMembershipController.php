<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreMembershipController extends Controller
{
    public function show(Request $request, Store $store): JsonResponse
    {
        $token = $request->attributes->get('sanctum_personal_access_token');
        $user = $request->user();

        abort_unless($user instanceof User && $token instanceof PersonalAccessToken, 401);

        $role = $user->roleForStore($store);

        abort_unless($role !== null, 403);

        $permissions = $this->permissionsForRole($role->value);

        if (! $token->can('*')) {
            $permissions = array_values(array_intersect($permissions, $token->abilities ?? []));
        }

        return response()->json([
            'data' => [
                'user_id' => $user->getKey(),
                'store_id' => $store->getKey(),
                'role' => $role->value,
                'email' => $user->email,
                'name' => $user->name,
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * @return list<string>
     */
    private function permissionsForRole(string $role): array
    {
        return match ($role) {
            'owner', 'admin' => [
                'manage-platform',
                'read-products',
                'write-products',
                'read-collections',
                'write-collections',
                'read-orders',
                'write-orders',
                'read-customers',
                'write-customers',
                'read-discounts',
                'write-discounts',
                'read-content',
                'write-content',
                'read-settings',
                'write-settings',
                'read-analytics',
                'write-themes',
                'manage-apps',
            ],
            'staff' => [
                'read-products',
                'write-products',
                'read-collections',
                'write-collections',
                'read-orders',
                'write-orders',
                'read-customers',
                'read-discounts',
                'write-discounts',
                'read-content',
                'write-content',
                'read-analytics',
            ],
            default => [
                'read-orders',
                'read-customers',
            ],
        };
    }
}

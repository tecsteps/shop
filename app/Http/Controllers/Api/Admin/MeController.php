<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    use ResolvesApiContext;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $store = $this->currentStoreModel($request);
        $role = $user->roleForStore($store);

        if ($role === null) {
            return response()->json([
                'message' => 'Membership not found for this store.',
            ], 404);
        }

        return response()->json([
            'user_id' => (int) $user->getAuthIdentifier(),
            'store_id' => (int) $store->id,
            'role' => (string) $role->value,
            'email' => $user->email,
            'name' => $user->name,
            'permissions' => $this->permissionsForRole((string) $role->value),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForRole(string $role): array
    {
        return match ($role) {
            'owner' => [
                'read-products',
                'write-products',
                'read-collections',
                'write-collections',
                'read-discounts',
                'write-discounts',
                'read-orders',
                'write-orders',
                'read-settings',
                'write-settings',
                'read-customers',
                'write-customers',
            ],
            'admin' => [
                'read-products',
                'write-products',
                'read-collections',
                'write-collections',
                'read-discounts',
                'write-discounts',
                'read-orders',
                'write-orders',
                'read-settings',
                'write-settings',
                'read-customers',
            ],
            'staff' => [
                'read-products',
                'write-products',
                'read-collections',
                'write-collections',
                'read-discounts',
                'write-discounts',
                'read-orders',
                'write-orders',
                'read-customers',
            ],
            'support' => [
                'read-orders',
                'read-customers',
            ],
            default => [],
        };
    }
}

<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\StoreInviteRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreInviteController extends Controller
{
    public function store(StoreInviteRequest $request, Store $store): JsonResponse
    {
        $this->authorizeInvite($request, $store);

        $validated = $request->validated();
        $userId = User::query()
            ->where('email', $validated['email'])
            ->value('id');

        if ($userId !== null && DB::table('store_users')->where('store_id', $store->getKey())->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'User already belongs to this store.'], 409);
        }

        $invitedAt = now();

        return response()->json([
            'data' => [
                'email' => $validated['email'],
                'role' => $validated['role'],
                'invited_at' => $invitedAt->toIso8601String(),
                'expires_at' => $invitedAt->copy()->addDays(7)->toIso8601String(),
            ],
        ], 201);
    }

    private function authorizeInvite(Request $request, Store $store): void
    {
        if ($request->attributes->has('sanctum_personal_access_token')) {
            return;
        }

        $role = $request->user()?->roleForStore($store);

        abort_unless(in_array($role?->value, ['owner', 'admin'], true), 403);
    }
}

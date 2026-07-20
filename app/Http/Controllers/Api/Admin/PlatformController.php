<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrganizationResource;
use App\Http\Resources\Admin\StoreResource;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Platform management endpoints (spec 02 §3.1). Restricted to tokens
 * with the manage-platform ability, except "me" which only needs a
 * valid token plus store membership.
 */
class PlatformController extends Controller
{
    /**
     * POST /api/admin/v1/platform/organizations — create an organization.
     */
    public function createOrganization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
        ]);

        $organization = Organization::query()->create($validated);

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/admin/v1/platform/stores — create a store in an organization.
     */
    public function createStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', Rule::unique('stores', 'handle')],
            'default_currency' => ['required', 'string', 'size:3', 'alpha'],
            'default_locale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $store = Store::query()->create(array_merge($validated, [
            'default_currency' => strtoupper($validated['default_currency']),
            'status' => StoreStatus::Active,
        ]));

        return (new StoreResource($store))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/invites — create-or-attach a
     * user with the given role. 409 when already a member.
     */
    public function invite(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(StoreUserRole::class)],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $user = User::query()->where('email', $validated['email'])->first();

        $isMember = $user !== null && StoreUser::query()
            ->where('store_id', $store->getKey())
            ->where('user_id', $user->getKey())
            ->exists();

        abort_if($isMember, 409, 'User is already a member of this store.');

        $user ??= User::query()->create([
            'name' => Str::before($validated['email'], '@'),
            'email' => $validated['email'],
            'password_hash' => Str::random(32),
        ]);

        StoreUser::query()->create([
            'store_id' => $store->getKey(),
            'user_id' => $user->getKey(),
            'role' => StoreUserRole::from($validated['role']),
        ]);

        return response()->json([
            'data' => [
                'email' => $user->email,
                'role' => $validated['role'],
                'invited_at' => now()->toIso8601ZuluString(),
                'expires_at' => now()->addDays(7)->toIso8601ZuluString(),
            ],
        ], 201);
    }

    /**
     * GET /api/admin/v1/stores/{storeId}/me — the token user's membership
     * details for the store.
     */
    public function me(Request $request, int $storeId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Store $store */
        $store = app('current_store');

        return response()->json([
            'data' => [
                'user_id' => $user->getKey(),
                'store_id' => $store->getKey(),
                'role' => $user->roleForStore($store)?->value,
                'email' => $user->email,
                'name' => $user->name,
                'permissions' => $user->currentAccessToken()?->abilities ?? [],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StoreUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInviteRequest;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\StorePlatformStoreRequest;
use App\Http\Resources\Admin\OrganizationResource;
use App\Http\Resources\Admin\StoreResource;
use App\Models\ApiToken;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformController extends Controller
{
    public function storeOrganization(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::query()->create($request->validated());

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(201);
    }

    public function storeStore(StorePlatformStoreRequest $request): JsonResponse
    {
        $store = Store::query()->create($request->validated());
        $token = $this->currentApiToken();

        if ($token instanceof ApiToken) {
            StoreUser::query()->firstOrCreate(
                [
                    'store_id' => $store->id,
                    'user_id' => $token->user_id,
                ],
                ['role' => StoreUserRole::Owner->value],
            );
        }

        return (new StoreResource($store))
            ->response()
            ->setStatusCode(201);
    }

    public function invite(StoreInviteRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if ($user instanceof User && StoreUser::query()->where('store_id', $store->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User is already a member of this store.'], 409);
        }

        if (! $user instanceof User) {
            $user = User::query()->create([
                'name' => $this->nameFromEmail($validated['email']),
                'email' => $validated['email'],
                'password_hash' => Hash::make(Str::random(32)),
                'status' => 'active',
            ]);
        }

        StoreUser::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
        ]);

        $invitedAt = now();

        return response()->json([
            'data' => [
                'email' => $validated['email'],
                'role' => $validated['role'],
                'invited_at' => $invitedAt->toISOString(),
                'expires_at' => $invitedAt->copy()->addDays(7)->toISOString(),
            ],
        ], 201);
    }

    public function me(Store $store): JsonResponse
    {
        $token = $this->currentApiToken();
        $user = $token?->user;
        $role = $user?->roleForStore($store);

        if (! $token instanceof ApiToken || ! $user instanceof User || $role === null) {
            abort(403, 'Not a member of this store.');
        }

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'store_id' => $store->id,
                'role' => $role->value,
                'email' => $user->email,
                'name' => $user->name,
                'permissions' => $token->abilities_json ?? [],
            ],
        ]);
    }

    private function currentApiToken(): ?ApiToken
    {
        return app()->bound('current_api_token')
            ? app('current_api_token')
            : null;
    }

    private function nameFromEmail(string $email): string
    {
        return Str::of($email)
            ->before('@')
            ->replace(['.', '_', '-'], ' ')
            ->title()
            ->toString();
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class PlatformController extends Controller
{
    public function organization(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'billing_email' => ['required', 'email', 'max:255']]);

        return response()->json(['data' => Organization::query()->create($data)], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'], 'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', 'max:63', 'unique:stores,handle'],
            'default_currency' => ['required', 'string', 'size:3'], 'default_locale' => ['required', 'string', 'max:10'], 'timezone' => ['required', 'timezone'],
        ]);
        $store = DB::transaction(function () use ($data, $request): Store {
            $store = Store::query()->create([...$data, 'status' => 'active']);
            StoreSettings::query()->create(['store_id' => $store->id, 'settings_json' => []]);
            DB::table('store_users')->insert([
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'role' => 'owner',
                'created_at' => now(),
            ]);

            return $store;
        });

        return response()->json(['data' => $store], 201);
    }

    public function invite(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'role' => ['required', 'in:owner,admin,staff,support']]);
        $actorRole = $request->user()->roleForStore(app('current_store'))?->value;
        abort_unless(in_array($actorRole, ['owner', 'admin'], true), 403);
        if ($data['role'] === 'owner' && DB::table('store_users')->where('store_id', $storeId)->where('role', 'owner')->exists()) {
            throw ValidationException::withMessages(['role' => 'Transfer ownership before assigning a new owner.']);
        }
        $user = User::query()->firstOrCreate(['email' => mb_strtolower($data['email'])], ['name' => str($data['email'])->before('@')->headline(), 'password_hash' => Hash::make(str()->password()), 'status' => 'active']);
        if ($user->stores()->whereKey($storeId)->exists()) {
            return response()->json(['message' => 'User is already a member.'], 409);
        }
        DB::table('store_users')->insert(['store_id' => $storeId, 'user_id' => $user->id, 'role' => $data['role'], 'created_at' => now()]);

        return response()->json(['data' => ['email' => $user->email, 'role' => $data['role'], 'invited_at' => now(), 'expires_at' => now()->addWeek()]], 201);
    }

    public function me(Request $request, int $storeId): JsonResponse
    {
        $role = $request->user()->roleForStore(app('current_store'));

        return response()->json(['data' => ['user_id' => $request->user()->id, 'store_id' => $storeId, 'role' => $role, 'email' => $request->user()->email, 'name' => $request->user()->name, 'permissions' => $request->user()->currentAccessToken()?->abilities ?? []]]);
    }
}

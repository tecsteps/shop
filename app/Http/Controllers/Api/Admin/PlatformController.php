<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StoreUserRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    public function organization(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255'], 'billing_email' => ['required', 'email']]);

        return response()->json(['data' => Organization::query()->create($validated)], Response::HTTP_CREATED);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['organization_id' => ['required', 'exists:organizations,id'], 'name' => ['required', 'string', 'max:255'], 'handle' => ['required', 'alpha_dash', 'unique:stores,handle'], 'default_currency' => ['sometimes', 'string', 'size:3'], 'default_locale' => ['sometimes', 'string'], 'timezone' => ['sometimes', 'timezone']]);

        return response()->json(['data' => Store::query()->create($validated)], Response::HTTP_CREATED);
    }

    public function invite(Request $request, Store $store): JsonResponse
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
        $validated = $request->validate(['email' => ['required', 'email'], 'name' => ['required', 'string', 'max:255'], 'role' => ['required', 'in:admin,staff,support']]);
        $user = DB::transaction(function () use ($validated, $store): User {
            $user = User::query()->firstOrCreate(['email' => $validated['email']], ['name' => $validated['name'], 'password_hash' => Hash::make(Str::random(32)), 'status' => 'active']);
            $store->users()->syncWithoutDetaching([$user->id => ['role' => StoreUserRole::from($validated['role'])->value, 'created_at' => now()]]);

            return $user;
        });

        return response()->json(['data' => $user], Response::HTTP_CREATED);
    }

    public function me(Request $request, Store $store): JsonResponse
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);

        return response()->json(['data' => ['user' => $request->user(), 'store' => $store, 'role' => $request->user()->roleForStore($store)]]);
    }
}

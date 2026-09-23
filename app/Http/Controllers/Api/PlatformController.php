<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Locale;
use ResourceBundle;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    public function createOrganization(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
        ]);

        $organization = Organization::query()->create($validated);

        return response()->json(['data' => $organization->only(['id', 'name', 'billing_email', 'created_at', 'updated_at'])], 201);
    }

    public function createStore(Request $request): Response
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', 'unique:stores,handle'],
            'default_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', function (string $attribute, mixed $value, \Closure $fail): void {
                $currencyBundle = class_exists(ResourceBundle::class) ? ResourceBundle::create('en', 'ICUDATA-curr') : null;
                $currencies = $currencyBundle instanceof ResourceBundle ? $currencyBundle->get('Currencies') : null;

                if (! $currencies instanceof ResourceBundle || ! $currencies->get($value) instanceof ResourceBundle) {
                    $fail('The selected currency must be a valid ISO 4217 code.');
                }
            }],
            'default_locale' => ['required', 'string', 'max:12', function (string $attribute, mixed $value, \Closure $fail): void {
                $normalizedLocale = Locale::canonicalize(str_replace('-', '_', $value));

                if (! ResourceBundle::getLocales('') || ! in_array($normalizedLocale, ResourceBundle::getLocales(''), true)) {
                    $fail('The selected locale must be a valid locale code.');
                }
            }],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $store = Store::query()->create([
            ...$validated,
            'status' => 'active',
        ]);

        return response()->json(['data' => $store->only([
            'id',
            'organization_id',
            'name',
            'handle',
            'status',
            'default_currency',
            'default_locale',
            'timezone',
            'created_at',
        ])], 201);
    }

    public function createInvitation(Request $request, int $storeId): Response
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:owner,admin,staff,support'],
        ]);
        $store = Store::query()->findOrFail($storeId);

        if ($store->users()->whereRaw('LOWER(users.email) = ?', [mb_strtolower($validated['email'])])->exists()) {
            return response()->json(['message' => 'This user is already a member of the store.'], 409);
        }

        $invitedAt = now();
        $expiresAt = $invitedAt->copy()->addDays(7);
        $token = Str::random(64);

        $invitation = StoreInvitation::query()->create([
            'store_id' => $store->id,
            'email' => mb_strtolower($validated['email']),
            'role' => $validated['role'],
            'token_hash' => hash('sha256', $token),
            'invited_at' => $invitedAt,
            'expires_at' => $expiresAt,
        ]);

        return response()->json(['data' => [
            'email' => $invitation->email,
            'role' => $invitation->role,
            'invited_at' => $invitation->invited_at,
            'expires_at' => $invitation->expires_at,
        ]], 201);
    }
}

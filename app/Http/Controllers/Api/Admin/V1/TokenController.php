<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreTokenRequest;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TokenController extends Controller
{
    public function store(StoreTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $abilities = $validated['abilities'] ?? [];

        if (in_array('manage-platform', $abilities, true) && Gate::denies('manage-platform')) {
            return response()->json([
                'message' => 'Only store owners can issue tokens with the manage-platform ability.',
            ], 403);
        }

        $expiresAt = isset($validated['expires_at']) ? now()->parse($validated['expires_at']) : null;

        $token = $request->user()->createToken($validated['name'], $abilities, $expiresAt);

        app(AuditLogger::class)->log('api_token.created', userId: $request->user()->getKey(), extra: [
            'token_name' => $validated['name'],
            'abilities' => $abilities,
        ]);

        return response()->json([
            'data' => [
                'id' => $token->accessToken->getKey(),
                'name' => $validated['name'],
                'abilities' => $abilities,
                'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
                'token' => $token->plainTextToken,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->firstOrFail();
        $token->delete();

        app(AuditLogger::class)->log('api_token.revoked', userId: $request->user()->getKey(), extra: [
            'token_name' => $token->name,
        ]);

        return response()->json(null, 204);
    }
}

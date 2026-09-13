<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\StoreUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\InviteStaffRequest;
use App\Http\Resources\Api\Admin\InviteResource;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StoreInviteController extends Controller
{
    public function store(InviteStaffRequest $request, int $storeId): JsonResponse
    {
        $store = Store::query()->findOrFail($storeId);
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['No user account exists for this email address.'],
            ]);
        }

        $alreadyMember = StoreUser::query()
            ->where('store_id', $store->getKey())
            ->where('user_id', $user->getKey())
            ->exists();

        if ($alreadyMember) {
            return response()->json([
                'message' => 'This user is already a member of the store.',
            ], 409);
        }

        $membership = StoreUser::query()->create([
            'store_id' => $store->getKey(),
            'user_id' => $user->getKey(),
            'role' => StoreUserRole::from($validated['role']),
        ]);

        $membership->load('user');

        return (new InviteResource($membership))->response()->setStatusCode(201);
    }
}

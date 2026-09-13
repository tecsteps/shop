<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Admin\MeResource;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request, int $storeId): JsonResponse
    {
        $store = Store::query()->findOrFail($storeId);

        $membership = StoreUser::query()
            ->where('store_id', $store->getKey())
            ->where('user_id', $request->user()->getKey())
            ->first();

        if ($membership === null) {
            return response()->json([
                'message' => 'You are not a member of this store.',
            ], 403);
        }

        $membership->load('user');

        return (new MeResource($membership))->response();
    }
}

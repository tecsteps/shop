<?php

namespace App\Http\Controllers\Api\Admin\V1\Platform;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CreateStoreRequest;
use App\Http\Resources\Api\Admin\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $store = Store::query()->create([
            ...$request->validated(),
            'status' => StoreStatus::Active,
        ]);

        return (new StoreResource($store))->response()->setStatusCode(201);
    }
}

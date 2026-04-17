<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $customers = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return CustomerResource::collection($customers)->response();
    }

    public function show(Request $request, int $storeId, int $customerId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $customer = Customer::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->findOrFail($customerId);

        return (new CustomerResource($customer))->response();
    }

    protected function resolveStore(Request $request, int $storeId): Store
    {
        $user = $request->user();
        $store = Store::query()->findOrFail($storeId);

        if ($user === null || ! $user->stores()->wherePivot('store_id', $store->getKey())->exists()) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $store;
    }
}

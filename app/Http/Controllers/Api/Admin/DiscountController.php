<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DiscountController extends Controller
{
    public function index(Store $store): AnonymousResourceCollection
    {
        $this->ensureStore($store);

        return DiscountResource::collection(Discount::query()->latest()->paginate(15));
    }

    public function store(StoreDiscountRequest $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $data = $request->validated();
        $data['store_id'] = $store->id;
        $data['code'] = isset($data['code']) ? Str::upper(Str::squish($data['code'])) : null;
        $this->ensureUniqueCode($store, $data['code']);
        $discount = Discount::query()->create($data);

        return (new DiscountResource($discount))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateDiscountRequest $request, Store $store, Discount $discount): DiscountResource
    {
        $this->ensureRelated($store, $discount);
        $data = $request->validated();

        if (array_key_exists('code', $data)) {
            $data['code'] = $data['code'] ? Str::upper(Str::squish($data['code'])) : null;
            $this->ensureUniqueCode($store, $data['code'], $discount->id);
        }

        $discount->update($data);

        return new DiscountResource($discount->refresh());
    }

    public function destroy(Store $store, Discount $discount): JsonResponse
    {
        $this->ensureRelated($store, $discount);
        $discount->delete();

        return response()->json(['deleted' => true]);
    }

    private function ensureUniqueCode(Store $store, ?string $code, ?int $excludeId = null): void
    {
        if ($code && Discount::query()->whereRaw('LOWER(code) = ?', [Str::lower($code)])->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))->exists()) {
            throw ValidationException::withMessages(['code' => 'The discount code has already been taken.']);
        }
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureRelated(Store $store, Discount $discount): void
    {
        $this->ensureStore($store);
        abort_unless($discount->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}

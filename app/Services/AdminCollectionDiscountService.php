<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Discount;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminCollectionDiscountService
{
    public function __construct(private readonly HandleGenerator $handles) {}

    /**
     * @param  array{status?: string, query?: string, page?: int}  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function listCollections(Store $store, array $filters, int $perPage): array
    {
        $query = Collection::query()
            ->where('store_id', $store->getKey())
            ->withCount('products');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['query'])) {
            $query->where('title', 'like', '%'.addcslashes($filters['query'], '%_\\').'%');
        }

        $paginator = $query
            ->orderBy('title')
            ->paginate($perPage, ['*'], 'page', $filters['page'] ?? 1);

        return $this->paginated($paginator, fn (Collection $collection): array => $this->collectionResource($collection));
    }

    /** @param array<string, mixed> $attributes */
    public function createCollection(Store $store, array $attributes): Collection
    {
        return DB::transaction(function () use ($store, $attributes): Collection {
            $collection = Collection::query()->create([
                'store_id' => $store->getKey(),
                'title' => $attributes['title'],
                'handle' => filled($attributes['handle'] ?? null)
                    ? $attributes['handle']
                    : $this->handles->generate($attributes['title'], 'collections', (int) $store->getKey()),
                'description_html' => $attributes['description_html'] ?? null,
                'type' => $attributes['type'],
                'status' => $attributes['status'] ?? 'active',
            ]);

            if (array_key_exists('product_ids', $attributes)) {
                $this->syncProducts($collection, $attributes['product_ids']);
            }

            return $collection->loadCount('products');
        });
    }

    /** @param array<string, mixed> $attributes */
    public function updateCollection(Collection $collection, array $attributes): Collection
    {
        return DB::transaction(function () use ($collection, $attributes): Collection {
            $changes = collect($attributes)->only(['title', 'description_html', 'type', 'status'])->all();

            if (array_key_exists('handle', $attributes)) {
                $title = $attributes['title'] ?? $collection->title;
                $changes['handle'] = filled($attributes['handle'])
                    ? $attributes['handle']
                    : $this->handles->generate($title, 'collections', (int) $collection->store_id, (int) $collection->getKey());
            }

            if ($changes !== []) {
                $collection->update($changes);
            }

            if (array_key_exists('product_ids', $attributes)) {
                $this->syncProducts($collection, $attributes['product_ids']);
            } elseif (array_key_exists('add_product_ids', $attributes) || array_key_exists('remove_product_ids', $attributes)) {
                $productIds = $collection->products()->pluck('products.id')->map(static fn (int|string $id): int => (int) $id)->all();
                $productIds = array_values(array_unique([...$productIds, ...($attributes['add_product_ids'] ?? [])]));
                $removed = array_map('intval', $attributes['remove_product_ids'] ?? []);
                $this->syncProducts($collection, array_values(array_diff($productIds, $removed)));
            }

            return $collection->refresh()->loadCount('products');
        });
    }

    public function deleteCollection(Collection $collection): void
    {
        $collection->delete();
    }

    /**
     * @param  array{type?: string, status?: string, page?: int}  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function listDiscounts(Store $store, array $filters, int $perPage): array
    {
        $query = Discount::query()->where('store_id', $store->getKey());

        if (isset($filters['type'])) {
            $filters['type'] === 'code'
                ? $query->whereNotNull('code')
                : $query->whereNull('code');
        }

        $now = now();

        if (($filters['status'] ?? null) === 'active') {
            $query
                ->where(fn ($builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($builder) => $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
        } elseif (($filters['status'] ?? null) === 'expired') {
            $query->whereNotNull('ends_at')->where('ends_at', '<', $now);
        } elseif (($filters['status'] ?? null) === 'scheduled') {
            $query->whereNotNull('starts_at')->where('starts_at', '>', $now);
        }

        $paginator = $query
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $filters['page'] ?? 1);

        return $this->paginated($paginator, fn (Discount $discount): array => $this->discountResource($discount));
    }

    /** @param array<string, mixed> $attributes */
    public function createDiscount(Store $store, array $attributes): Discount
    {
        $mode = $attributes['type'];
        $rules = $this->storedRules($mode, $attributes['rules_json'] ?? [], []);
        $valueType = $this->toStorageValueType($attributes['value_type']);

        return Discount::query()->create([
            'store_id' => $store->getKey(),
            'code' => $mode === 'code' ? $attributes['code'] : null,
            'title' => $mode === 'code' ? $attributes['code'] : 'Automatic discount',
            'type' => $valueType,
            'value' => $valueType === 'free_shipping' ? 0 : (int) $attributes['value_amount'],
            'minimum_subtotal_amount' => $rules['minimum_purchase_amount'],
            'usage_limit' => $attributes['usage_limit'] ?? null,
            'starts_at' => $attributes['starts_at'] ?? null,
            'ends_at' => $attributes['ends_at'] ?? null,
            'is_active' => true,
            'rules_json' => $rules,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    public function updateDiscount(Discount $discount, array $attributes): Discount
    {
        $changes = [];

        if (array_key_exists('value_type', $attributes)) {
            $changes['type'] = $this->toStorageValueType($attributes['value_type']);
        }

        if (array_key_exists('value_amount', $attributes)) {
            $valueType = $changes['type'] ?? $discount->type;
            $changes['value'] = $valueType === 'free_shipping' ? 0 : (int) $attributes['value_amount'];
        } elseif (($changes['type'] ?? $discount->type) === 'free_shipping') {
            $changes['value'] = 0;
        }

        foreach (['usage_limit', 'starts_at', 'ends_at'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $changes[$field] = $attributes[$field];
            }
        }

        if (array_key_exists('rules_json', $attributes)) {
            $rules = $this->storedRules(
                $discount->code === null ? 'automatic' : 'code',
                $attributes['rules_json'],
                $this->publicRules($discount),
            );
            $changes['rules_json'] = $rules;
            $changes['minimum_subtotal_amount'] = $rules['minimum_purchase_amount'];
        }

        if ($changes !== []) {
            $discount->update($changes);
        }

        return $discount->refresh();
    }

    public function deleteDiscount(Discount $discount): void
    {
        $discount->delete();
    }

    /** @return array<string, mixed> */
    public function collectionResource(Collection $collection): array
    {
        return [
            'id' => (int) $collection->getKey(),
            'store_id' => (int) $collection->store_id,
            'title' => $collection->title,
            'handle' => $collection->handle,
            'description_html' => $collection->description_html,
            'type' => $collection->type,
            'status' => $collection->status,
            'products_count' => (int) ($collection->products_count ?? $collection->products()->count()),
            'created_at' => $collection->created_at?->toISOString(),
            'updated_at' => $collection->updated_at?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    public function discountResource(Discount $discount): array
    {
        $rules = $this->publicRules($discount);

        return [
            'id' => (int) $discount->getKey(),
            'store_id' => (int) $discount->store_id,
            'type' => $discount->code === null ? 'automatic' : 'code',
            'code' => $discount->code,
            'value_type' => $this->fromStorageValueType($discount->type),
            'value_amount' => (int) $discount->value,
            'starts_at' => $discount->starts_at?->toISOString(),
            'ends_at' => $discount->ends_at?->toISOString(),
            'usage_limit' => $discount->usage_limit === null ? null : (int) $discount->usage_limit,
            'usage_count' => (int) $discount->usage_count,
            'rules_json' => $rules,
            'created_at' => $discount->created_at?->toISOString(),
        ];
    }

    /** @param list<int> $productIds */
    private function syncProducts(Collection $collection, array $productIds): void
    {
        $positions = [];

        foreach (array_values(array_unique(array_map('intval', $productIds))) as $position => $productId) {
            $positions[$productId] = ['position' => $position];
        }

        $collection->products()->sync($positions);
    }

    /**
     * @param  array<string, mixed>  $newRules
     * @param  array<string, mixed>  $existingRules
     * @return array<string, mixed>
     */
    private function storedRules(string $mode, array $newRules, array $existingRules): array
    {
        $rules = array_merge([
            'minimum_purchase_amount' => 0,
            'applicable_product_ids' => [],
            'applicable_collection_ids' => [],
            'customer_eligibility' => 'all',
            'once_per_customer' => false,
        ], $existingRules, $newRules);
        $productIds = array_values(array_unique(array_map('intval', $rules['applicable_product_ids'] ?? [])));
        $collectionIds = array_values(array_unique(array_map('intval', $rules['applicable_collection_ids'] ?? [])));

        return [
            'activation_method' => $mode,
            'minimum_purchase_amount' => (int) ($rules['minimum_purchase_amount'] ?? 0),
            'applicable_product_ids' => $productIds,
            'applicable_collection_ids' => $collectionIds,
            'customer_eligibility' => $rules['customer_eligibility'] ?? 'all',
            'once_per_customer' => (bool) ($rules['once_per_customer'] ?? false),
            'one_per_customer' => (bool) ($rules['once_per_customer'] ?? false),
            'product_ids' => $productIds,
            'collection_ids' => $collectionIds,
        ];
    }

    /** @return array{minimum_purchase_amount: int, applicable_product_ids: list<int>, applicable_collection_ids: list<int>, customer_eligibility: string, once_per_customer: bool} */
    private function publicRules(Discount $discount): array
    {
        $storedRules = $discount->rules_json ?? [];

        return [
            'minimum_purchase_amount' => (int) ($storedRules['minimum_purchase_amount'] ?? $discount->minimum_subtotal_amount ?? 0),
            'applicable_product_ids' => array_values(array_map('intval', $storedRules['applicable_product_ids'] ?? $storedRules['product_ids'] ?? [])),
            'applicable_collection_ids' => array_values(array_map('intval', $storedRules['applicable_collection_ids'] ?? $storedRules['collection_ids'] ?? [])),
            'customer_eligibility' => (string) ($storedRules['customer_eligibility'] ?? 'all'),
            'once_per_customer' => (bool) ($storedRules['once_per_customer'] ?? $storedRules['one_per_customer'] ?? false),
        ];
    }

    private function toStorageValueType(string $valueType): string
    {
        return match ($valueType) {
            'percent' => 'percentage',
            'fixed' => 'fixed_amount',
            'free_shipping' => 'free_shipping',
        };
    }

    private function fromStorageValueType(string $valueType): string
    {
        return match ($valueType) {
            'percentage' => 'percent',
            'fixed_amount' => 'fixed',
            'free_shipping' => 'free_shipping',
            default => $valueType,
        };
    }

    /**
     * @template T of Collection|Discount
     *
     * @param  LengthAwarePaginator<T>  $paginator
     * @param  callable(T): array<string, mixed>  $resource
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    private function paginated(LengthAwarePaginator $paginator, callable $resource): array
    {
        return [
            'data' => $paginator->getCollection()->map($resource)->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}

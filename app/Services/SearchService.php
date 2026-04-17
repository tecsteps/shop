<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function __construct(public int $limit = 20) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Product>
     */
    public function search(Store $store, string $query, array $filters = [], ?string $sessionId = null, bool $log = true): Collection
    {
        $trimmed = trim($query);

        if ($trimmed === '') {
            if ($log) {
                $this->log($store, $trimmed, $filters, 0, $sessionId);
            }

            /** @var Collection<int, Product> $empty */
            $empty = new Collection;

            return $empty;
        }

        $match = $this->buildMatchExpression($trimmed);

        if ($match === '') {
            if ($log) {
                $this->log($store, $trimmed, $filters, 0, $sessionId);
            }

            /** @var Collection<int, Product> $empty */
            $empty = new Collection;

            return $empty;
        }

        $rows = DB::table('products_fts')
            ->selectRaw('product_id, bm25(products_fts) AS rank')
            ->whereRaw('products_fts MATCH ?', [$match])
            ->where('store_id', $store->getKey())
            ->orderBy('rank')
            ->limit($this->limit)
            ->get();

        $ids = $rows->pluck('product_id')->map(fn ($id): int => (int) $id)->all();

        if ($ids === []) {
            if ($log) {
                $this->log($store, $trimmed, $filters, 0, $sessionId);
            }

            /** @var Collection<int, Product> $empty */
            $empty = new Collection;

            return $empty;
        }

        $products = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereIn('id', $ids)
            ->where('status', ProductStatus::Active->value)
            ->whereNotNull('published_at')
            ->with(['variants' => fn ($q) => $q->orderBy('position')])
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->getKey(), $ids, true))
            ->values();

        if ($log) {
            $this->log($store, $trimmed, $filters, $products->count(), $sessionId);
        }

        return Collection::make($products);
    }

    protected function buildMatchExpression(string $query): string
    {
        $sanitized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query) ?? '';
        $tokens = preg_split('/\s+/', trim($sanitized)) ?: [];
        $reserved = ['AND', 'OR', 'NOT', 'NEAR'];
        $tokens = array_values(array_filter(
            $tokens,
            fn (string $t): bool => $t !== '' && ! in_array(strtoupper($t), $reserved, true),
        ));

        if ($tokens === []) {
            return '';
        }

        $last = array_pop($tokens);
        $pieces = array_map(fn (string $t): string => $this->quoteToken($t), $tokens);
        $pieces[] = $this->quoteToken($last).'*';

        return implode(' ', $pieces);
    }

    protected function quoteToken(string $token): string
    {
        return '"'.str_replace('"', '', $token).'"';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function log(Store $store, string $query, array $filters, int $resultsCount, ?string $sessionId): void
    {
        SearchQuery::query()->create([
            'store_id' => $store->getKey(),
            'query' => $query,
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $resultsCount,
            'session_id' => $sessionId,
            'created_at' => now(),
        ]);
    }
}

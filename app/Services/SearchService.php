<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    public function fullTextExpression(int $storeId, string $query, bool $prefixLastTerm = false): string
    {
        $terms = collect(preg_split('/\s+/u', preg_replace('/[^\pL\pN ]/u', ' ', $query) ?? '') ?: [])
            ->map(static fn (string $term): string => Str::lower(trim($term)))
            ->filter()
            ->values();

        if ($terms->isEmpty()) {
            return '';
        }

        $settings = DB::table('search_settings')->where('store_id', $storeId)->first();
        $stopWords = collect(json_decode((string) ($settings->stop_words_json ?? '[]'), true) ?: [])
            ->map(static fn (mixed $word): string => Str::lower(trim((string) $word)))
            ->flip();
        $synonyms = json_decode((string) ($settings->synonyms_json ?? '[]'), true) ?: [];
        $expandedTerms = [];
        $lastTermPosition = $terms->count() - 1;

        foreach ($terms as $position => $term) {
            if ($stopWords->has($term)) {
                continue;
            }

            $alternatives = [$term];

            foreach ($synonyms as $group) {
                if (! is_array($group)) {
                    continue;
                }

                $normalizedGroup = collect($group)
                    ->map(static fn (mixed $word): string => Str::lower(trim((string) $word)))
                    ->filter()
                    ->values();

                if ($normalizedGroup->contains($term)) {
                    $alternatives = [...$alternatives, ...$normalizedGroup->all()];
                }
            }

            foreach (array_unique($alternatives) as $alternative) {
                $isPrefix = $prefixLastTerm && $position === $lastTermPosition;
                $expandedTerms[] = '"'.str_replace('"', '""', $alternative).'"'.($isPrefix ? '*' : '');
            }
        }

        return implode(' OR ', array_unique($expandedTerms));
    }

    /** @return list<string> */
    public function rankedProductIds(int $storeId, string $fullTextExpression, int $limit = 500): array
    {
        if ($fullTextExpression === '') {
            return [];
        }

        $matches = DB::select(
            'SELECT product_id FROM products_fts WHERE products_fts MATCH ? AND store_id = ? ORDER BY bm25(products_fts) LIMIT ?',
            [$fullTextExpression, (string) $storeId, min(max($limit, 1), 1000)],
        );

        return array_map(static fn (object $match): string => (string) $match->product_id, $matches);
    }
}

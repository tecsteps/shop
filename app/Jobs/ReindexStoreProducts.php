<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReindexStoreProducts implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $storeId) {}

    public function handle(): void
    {
        $startedAt = microtime(true);
        DB::table('search_settings')->insertOrIgnore(['store_id' => $this->storeId]);
        DB::table('search_settings')->where('store_id', $this->storeId)->update(['index_status' => 'processing']);

        try {
            $productCount = Product::withoutGlobalScopes()->where('store_id', $this->storeId)->count();
            DB::table('products_fts')->where('store_id', (string) $this->storeId)->delete();
            DB::table('search_settings')->where('store_id', $this->storeId)->update([
                'documents_count' => 0,
                'pending_updates' => $productCount,
            ]);

            Product::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->orderBy('id')
                ->chunkById(500, function ($products): void {
                    $documents = $products->map(static fn (Product $product): array => [
                        'store_id' => (string) $product->store_id,
                        'product_id' => (string) $product->id,
                        'title' => $product->title,
                        'description' => strip_tags((string) $product->description_html),
                        'vendor' => (string) $product->vendor,
                        'product_type' => (string) $product->product_type,
                        'tags' => implode(' ', $product->tags ?? []),
                    ])->all();

                    if ($documents !== []) {
                        DB::table('products_fts')->insert($documents);
                        DB::table('search_settings')->where('store_id', $this->storeId)->update([
                            'documents_count' => DB::raw('documents_count + '.count($documents)),
                            'pending_updates' => DB::raw('MAX(0, pending_updates - '.count($documents).')'),
                        ]);
                    }
                });

            DB::table('search_settings')->where('store_id', $this->storeId)->update([
                'index_status' => 'ready',
                'last_reindex_at' => now(),
                'last_reindex_duration_seconds' => (int) ceil(microtime(true) - $startedAt),
                'documents_count' => DB::table('products_fts')->where('store_id', (string) $this->storeId)->count(),
                'pending_updates' => 0,
            ]);
        } catch (Throwable $exception) {
            DB::table('search_settings')->where('store_id', $this->storeId)->update(['index_status' => 'failed']);

            throw $exception;
        }
    }
}

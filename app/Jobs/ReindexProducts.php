<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ReindexProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $storeId) {}

    public function handle(SearchService $search): void
    {
        $startedAt = microtime(true);
        $previous = app()->bound('current_store') ? app('current_store') : null;
        $store = Store::query()->findOrFail($this->storeId);
        app()->instance('current_store', $store);

        try {
            $products = Product::withoutGlobalScopes()->where('store_id', $store->id)->orderBy('id')->get();
            $total = max(1, $products->count());
            $this->status('processing', 0, ['pending_updates' => $products->count()]);
            foreach ($products as $index => $product) {
                $search->syncProduct($product);
                $this->status('processing', (int) round((($index + 1) / $total) * 100), [
                    'pending_updates' => max(0, $products->count() - $index - 1),
                ]);
            }
            $this->status('ready', 100, [
                'last_reindex_at' => now()->toIso8601String(),
                'last_reindex_duration_seconds' => max(0, (int) round(microtime(true) - $startedAt)),
                'documents_count' => $products->count(),
                'pending_updates' => 0,
            ]);
        } catch (Throwable $exception) {
            $this->status('failed', 0, ['pending_updates' => 0]);
            throw $exception;
        } finally {
            if ($previous !== null) {
                app()->instance('current_store', $previous);
            } else {
                app()->forgetInstance('current_store');
            }
        }
    }

    /** @param array<string, mixed> $details */
    private function status(string $status, int $progress, array $details = []): void
    {
        $record = StoreSettings::withoutGlobalScopes()->firstOrNew(['store_id' => $this->storeId]);
        $settings = (array) $record->settings_json;
        data_set($settings, 'search.status', $status);
        data_set($settings, 'search.progress', $progress);
        foreach ($details as $key => $value) {
            data_set($settings, 'search.'.$key, $value);
        }
        $record->settings_json = $settings;
        $record->save();
    }
}

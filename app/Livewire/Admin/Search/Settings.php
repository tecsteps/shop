<?php

namespace App\Livewire\Admin\Search;

use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Settings extends Component
{
    public string $synonyms = '';

    public string $stopWords = '';

    public int $reindexed = 0;

    public function mount(): void
    {
        if (! Schema::hasTable('search_settings')) {
            return;
        }

        $store = app('current_store');
        $row = DB::table('search_settings')->where('store_id', $store->id)->first();
        if ($row) {
            $this->synonyms = implode(', ', json_decode($row->synonyms_json ?? '[]', true) ?: []);
            $this->stopWords = implode(', ', json_decode($row->stop_words_json ?? '[]', true) ?: []);
        }
    }

    public function save(): void
    {
        if (! Schema::hasTable('search_settings')) {
            session()->flash('error', 'Search settings table unavailable.');

            return;
        }

        $store = app('current_store');
        $synonyms = array_values(array_filter(array_map('trim', explode(',', $this->synonyms))));
        $stopWords = array_values(array_filter(array_map('trim', explode(',', $this->stopWords))));

        DB::table('search_settings')->updateOrInsert(
            ['store_id' => $store->id],
            [
                'synonyms_json' => json_encode($synonyms),
                'stop_words_json' => json_encode($stopWords),
                'updated_at' => now(),
            ],
        );

        session()->flash('success', 'Search settings saved.');
    }

    public function reindex(SearchService $service): void
    {
        $count = 0;
        foreach (Product::query()->cursor() as $product) {
            $service->syncProduct($product);
            $count++;
        }

        $this->reindexed = $count;
        session()->flash('success', "Reindexed {$count} products.");
    }

    public function render()
    {
        return view('livewire.admin.search.settings');
    }
}

<?php

namespace App\Livewire\Storefront\Search;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Modal extends Component
{
    public bool $open = false;

    public string $query = '';

    /**
     * @var array<int, array{id: int, title: string, handle: string}>
     */
    public array $results = [];

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if (! $this->open) {
            $this->query = '';
            $this->results = [];
        }
    }

    public function updatedQuery(): void
    {
        $this->results = $this->searchProducts();
    }

    public function render()
    {
        return view('livewire.storefront.search.modal');
    }

    /**
     * @return array<int, array{id: int, title: string, handle: string}>
     */
    protected function searchProducts(): array
    {
        if (! Schema::hasTable('products') || ! app()->bound('current_store') || trim($this->query) === '') {
            return [];
        }

        return DB::table('products')
            ->where('store_id', app('current_store')->id)
            ->where('status', 'active')
            ->where('title', 'like', '%'.$this->query.'%')
            ->orderBy('title')
            ->limit(8)
            ->get(['id', 'title', 'handle'])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'title' => $row->title,
                'handle' => $row->handle,
            ])
            ->all();
    }
}

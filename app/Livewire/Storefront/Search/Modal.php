<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Collection;
use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public bool $isOpen = false;

    public string $query = '';

    #[On('open-search-modal')]
    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    public function search(): mixed
    {
        $query = trim($this->query);

        return $this->redirect(route('storefront.search', ['q' => $query]));
    }

    public function render(): mixed
    {
        $query = trim($this->query);
        $products = collect();
        $collections = collect();

        if ($query !== '') {
            $needle = addcslashes($query, '%_\\');
            $products = Product::query()
                ->where('status', 'active')
                ->whereNotNull('published_at')
                ->where(fn ($builder) => $builder->where('title', 'like', '%'.$needle.'%')->orWhere('description_html', 'like', '%'.$needle.'%'))
                ->with(['variants.inventoryItem', 'media'])
                ->limit(5)
                ->get();
            $collections = Collection::query()
                ->where('status', 'active')
                ->where('title', 'like', '%'.$needle.'%')
                ->limit(3)
                ->get();
        }

        return view('storefront.components.search-modal', compact('products', 'collections'));
    }
}

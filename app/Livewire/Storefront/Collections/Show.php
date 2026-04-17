<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\ProductStatus;
use App\Models\Collection;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public ?Collection $collection = null;

    public LaravelCollection $products;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
        $this->products = collect();

        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->first();

        if ($this->collection !== null) {
            $this->products = $this->collection
                ->products()
                ->where('status', ProductStatus::Active)
                ->whereNotNull('published_at')
                ->with(['variants' => fn ($q) => $q->orderBy('position')->orderBy('id')])
                ->orderBy('title')
                ->get();
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.collections.show');
    }
}

<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public bool $isOpen = false;

    public string $query = '';

    public int $selectedIndex = -1;

    #[On('open-search-modal')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->query = '';
        $this->selectedIndex = -1;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->selectedIndex = -1;
    }

    public function updatedQuery(): void
    {
        $this->selectedIndex = -1;
    }

    #[Computed]
    public function suggestions(): Collection
    {
        if (trim($this->query) === '' || ! app()->bound('current_store')) {
            return collect();
        }

        $store = app('current_store');
        $service = app(SearchService::class);

        return $service->autocomplete($store, $this->query, 5);
    }

    public function navigateUp(): void
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    public function navigateDown(): void
    {
        $maxIndex = $this->suggestions->count() - 1;
        if ($this->selectedIndex < $maxIndex) {
            $this->selectedIndex++;
        }
    }

    public function selectCurrent(): void
    {
        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->suggestions->count()) {
            $product = $this->suggestions->get($this->selectedIndex);
            if ($product) {
                $this->redirect(route('storefront.products.show', $product->handle), navigate: true);

                return;
            }
        }

        $this->goToSearch();
    }

    public function goToSearch(): void
    {
        if (trim($this->query) !== '') {
            $this->redirect(route('storefront.search', ['q' => $this->query]), navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.storefront.search.modal');
    }
}

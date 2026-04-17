<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public ?Collection $collection = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $this->collection = Collection::query()
            ->where('handle', $handle)
            ->first();
    }

    public function render(): View
    {
        return view('livewire.storefront.collections.show');
    }
}

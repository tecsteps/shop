<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Collections')]
class Index extends Component
{
    #[Computed]
    public function collections(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.storefront.collections.index')
            ->layout('storefront.layouts.app', ['title' => 'Collections']);
    }
}

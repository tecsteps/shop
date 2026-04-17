<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Home extends Component
{
    public function render(): View
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->limit(6)
            ->get();

        return view('livewire.storefront.home', [
            'collections' => $collections,
        ]);
    }
}

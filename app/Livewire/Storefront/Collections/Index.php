<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        /** @var LengthAwarePaginator<int, Collection> $collections */
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->paginate(12);

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}

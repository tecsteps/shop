<?php

namespace App\Livewire\Storefront\Collections;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use InteractsWithStore;

    public int $page = 1;

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    #[Computed]
    public function collections(): LengthAwarePaginator
    {
        return $this->store()->collections()
            ->where('status', 'active')
            ->orderBy('title')
            ->paginate(12, ['*'], 'page', $this->page);
    }
}

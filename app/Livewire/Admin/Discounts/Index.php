<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $query = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id);

        if ($this->search) {
            $query->where('code', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $discounts = $query->latest()->paginate(15);

        return view('livewire.admin.discounts.index', [
            'discounts' => $discounts,
        ]);
    }
}

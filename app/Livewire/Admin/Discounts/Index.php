<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Discount list: searchable by code, filterable by lifecycle status, paginated.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Discount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getDiscountsProperty()
    {
        $now = now();

        return Discount::query()
            ->when($this->search !== '', fn (Builder $q) => $q->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter === 'active', fn (Builder $q) => $q
                ->where('status', 'active')
                ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now)))
            ->when($this->statusFilter === 'expired', fn (Builder $q) => $q->whereNotNull('ends_at')->where('ends_at', '<', $now))
            ->when($this->statusFilter === 'scheduled', fn (Builder $q) => $q->whereNotNull('starts_at')->where('starts_at', '>', $now))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.discounts.index');
    }
}

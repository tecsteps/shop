<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Customer list: searchable by name/email, with order count and lifetime spend.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getCustomersProperty()
    {
        return Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->when($this->search !== '', fn (Builder $q) => $q->where(function (Builder $q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.customers.index');
    }
}

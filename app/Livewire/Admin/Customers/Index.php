<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Admin\AdminComponent;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorizeAction('viewAny', Customer::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        return Customer::query()->withCount('orders')->withSum('orders', 'total_amount')
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
            ->latest('created_at')->paginate(25);
    }

    public function render(): View
    {
        return $this->admin(view('admin.customers.index'), 'Customers', [['label' => 'Customers']]);
    }
}

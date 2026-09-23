<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render(): mixed
    {
        Gate::authorize('viewAny', Customer::class);
        $customers = Customer::query()->withCount('orders')->when($this->search !== '', fn ($query) => $query->where(static fn ($nested) => $nested->where('email', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%')))->orderBy('name');

        return view('livewire.admin.customers.index', ['customers' => $customers->paginate(20)])->layout('layouts.admin');
    }
}

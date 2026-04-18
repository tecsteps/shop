<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        $query = Customer::query()
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('email', 'like', '%'.$this->search.'%')
                        ->orWhere('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('id');

        return view('livewire.admin.customers.index', [
            'customers' => $query->paginate(20),
        ]);
    }
}

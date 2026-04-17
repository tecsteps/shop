<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public int $customerId;

    public function mount(int $customer): void
    {
        $model = Customer::query()->findOrFail($customer);
        $this->authorize('view', $model);
        $this->customerId = (int) $model->getKey();
    }

    public function render(): View
    {
        $customer = Customer::query()
            ->with(['orders' => fn ($q) => $q->orderByDesc('placed_at')->limit(25), 'addresses'])
            ->findOrFail($this->customerId);

        return view('livewire.admin.customers.show', [
            'customer' => $customer,
        ]);
    }
}

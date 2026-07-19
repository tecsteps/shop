<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Customer $customer;

    public string $name = '';

    public bool $marketingOptIn = false;

    public bool $showEditModal = false;

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);

        $customer->loadMissing('addresses');

        $this->customer = $customer;
    }

    /**
     * Open the edit modal with the current values preloaded.
     */
    public function openEditModal(): void
    {
        $this->authorize('update', $this->customer);

        $this->resetValidation();
        $this->name = (string) ($this->customer->name ?? '');
        $this->marketingOptIn = $this->customer->marketing_opt_in;
        $this->showEditModal = true;
    }

    /**
     * Update the customer's name and marketing opt-in (spec 03 §9.2).
     */
    public function saveCustomer(): void
    {
        $this->authorize('update', $this->customer);

        $validated = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'marketingOptIn' => ['boolean'],
        ]);

        $name = trim((string) ($validated['name'] ?? ''));

        $this->customer->update([
            'name' => $name !== '' ? $name : null,
            'marketing_opt_in' => (bool) ($validated['marketingOptIn'] ?? false),
        ]);

        $this->showEditModal = false;
        $this->customer->refresh();

        $this->dispatch('toast', type: 'success', message: 'Customer saved');
    }

    public function render(): View
    {
        $orders = $this->customer->orders()
            ->latest('placed_at')
            ->paginate(10);

        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $ordersCount = $this->customer->orders()->count();
        $totalSpent = (int) $this->customer->orders()->sum('total_amount');

        return view('livewire.admin.customers.show', [
            'customer' => $this->customer,
            'orders' => $orders,
            'ordersCount' => $ordersCount,
            'totalSpent' => $totalSpent,
            'averageOrderValue' => $ordersCount > 0 ? intdiv($totalSpent, $ordersCount) : 0,
            'currency' => $store->default_currency,
        ])->layout('admin.layouts.app')->title($this->customer->name ?? $this->customer->email);
    }
}

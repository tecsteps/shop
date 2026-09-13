<?php

namespace App\Livewire\Storefront\Account;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    use ResolvesStore;

    public string $name = '';

    public string $email = '';

    public bool $marketingOptIn = false;

    public ?string $status = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        $this->name = (string) ($customer->name ?? '');
        $this->email = (string) $customer->email;
        $this->marketingOptIn = (bool) $customer->marketing_opt_in;
    }

    public function save(): void
    {
        $customer = Auth::guard('customer')->user();

        abort_if($customer === null, 401);

        $this->validate([
            'name' => 'nullable|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('customers', 'email')
                    ->where('store_id', $this->store()->getKey())
                    ->ignore($customer->getKey()),
            ],
            'marketingOptIn' => 'boolean',
        ]);

        $customer->name = $this->name !== '' ? $this->name : null;
        $customer->email = $this->email;
        $customer->marketing_opt_in = $this->marketingOptIn;
        $customer->save();

        $this->status = 'Profile updated successfully.';
    }

    public function render()
    {
        $store = $this->store();

        return view('livewire.storefront.account.profile', [
            'store' => $store,
        ])->layout('storefront.layouts.app', [
            'title' => 'Profile - '.$store->name,
            'metaDescription' => 'Update your profile at '.$store->name,
        ]);
    }
}

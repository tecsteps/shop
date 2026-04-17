<?php

namespace App\Livewire\Storefront\Account;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public bool $marketing_opt_in = false;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $status = '';

    public function mount(): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $this->name = (string) $customer->name;
        $this->email = (string) $customer->email;
        $this->marketing_opt_in = (bool) $customer->marketing_opt_in;
    }

    public function saveProfile(): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($q) => $q->where('store_id', $customer->store_id))
                    ->ignore($customer->getKey()),
            ],
            'marketing_opt_in' => 'boolean',
        ]);

        $customer->name = $this->name;
        $customer->email = $this->email;
        $customer->marketing_opt_in = $this->marketing_opt_in ? 1 : 0;
        $customer->save();

        $this->status = 'Profile updated.';
    }

    public function changePassword(): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $customer->hasPassword() || ! Hash::check($this->current_password, $customer->getAuthPassword())) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }

        $customer->password_hash = Hash::make($this->password);
        $customer->save();

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->status = 'Password updated.';
    }

    public function render(): View
    {
        return view('livewire.storefront.account.profile');
    }
}

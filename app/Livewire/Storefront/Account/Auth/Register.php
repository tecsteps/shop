<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Cart;
use App\Models\Customer;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Register extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): mixed
    {
        $store = app('current_store');
        $this->resetErrorBag();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')->where('store_id', $store->id),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'state' => 'active',
            'email_verified_at' => now(),
        ]);

        Auth::guard('customer')->login($customer);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->mergeGuestCart($customer);

        return redirect()->route('account.dashboard');
    }

    protected function mergeGuestCart(Customer $customer): void
    {
        if (! app()->bound('current_store')) {
            return;
        }

        $store = app('current_store');
        $cartService = app(CartService::class);
        $session = session();
        $guestCartId = $session->get(CartService::SESSION_KEY);

        $guest = $guestCartId
            ? Cart::query()->where('store_id', $store->id)->find($guestCartId)
            : null;

        $customerCart = $cartService->getOrCreateForSession($store, $customer);

        if ($guest && $guest->id !== $customerCart->id) {
            $cartService->mergeOnLogin($guest, $customerCart);
            $session->put(CartService::SESSION_KEY, $customerCart->id);
        }
    }

    public function render()
    {
        return view('livewire.storefront.account.auth.register');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerAccountController extends Controller
{
    public function login(): View
    {
        return view('storefront.account.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt($credentials, true)) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        Auth::guard('customer')->user()->update(['last_login_at' => now()]);

        return redirect()->route('account.dashboard');
    }

    public function register(): View
    {
        return view('storefront.account.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email,NULL,id,store_id,'.app('current_store')->id],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $customer = Customer::query()->create([
            'store_id' => app('current_store')->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::guard('customer')->login($customer);

        return redirect()->route('account.dashboard');
    }

    public function dashboard(): View
    {
        $orders = Auth::guard('customer')->user()->orders()->latest()->limit(5)->get();

        return view('storefront.account.dashboard', compact('orders'));
    }

    public function orders(): View
    {
        $orders = Auth::guard('customer')->user()->orders()->latest()->get();

        return view('storefront.account.orders', compact('orders'));
    }

    public function order(string $orderNumber): View
    {
        $order = Auth::guard('customer')->user()->orders()->where('order_number', $orderNumber)->with('lines')->firstOrFail();

        return view('storefront.account.order', compact('order'));
    }

    public function addresses(): View
    {
        $addresses = Auth::guard('customer')->user()->addresses()->latest()->get();

        return view('storefront.account.addresses', compact('addresses'));
    }

    public function saveAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        CustomerAddress::query()->updateOrCreate(
            ['id' => $validated['id'] ?? null, 'customer_id' => Auth::guard('customer')->id()],
            [
                'store_id' => app('current_store')->id,
                'name' => $validated['name'],
                'address1' => $validated['address1'],
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'country_code' => strtoupper($validated['country_code']),
            ],
        );

        return back()->with('status', 'Address saved.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('account.login');
    }
}

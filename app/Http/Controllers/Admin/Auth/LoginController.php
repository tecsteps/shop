<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    use ResolvesAdminContext;

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $attempted = Auth::guard('web')->attempt(
            [
                'email' => (string) $credentials['email'],
                'password' => (string) $credentials['password'],
            ],
            (bool) ($credentials['remember'] ?? false),
        );

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        $request->session()->regenerate();

        $storeId = $this->currentStoreId($request);
        $userId = $request->user()?->getAuthIdentifier();

        if (
            $userId === null
            || ! DB::table('store_users')
                ->where('store_id', $storeId)
                ->where('user_id', $userId)
                ->exists()
        ) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('Invalid credentials.'),
            ]);
        }

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login');
    }
}

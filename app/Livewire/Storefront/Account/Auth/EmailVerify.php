<?php

namespace App\Livewire\Storefront\Account\Auth;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;

class EmailVerify
{
    public function __invoke(int $id, string $hash): RedirectResponse
    {
        $customer = Customer::query()->withoutGlobalScopes()->findOrFail($id);

        if (! hash_equals(sha1((string) $customer->email), $hash)) {
            abort(403);
        }

        if ($customer->email_verified_at === null) {
            $customer->email_verified_at = now();
            $customer->save();
        }

        return redirect('/account');
    }
}

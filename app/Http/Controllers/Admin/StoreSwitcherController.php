<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreSwitcherController extends Controller
{
    public function __invoke(Request $request, int $store): RedirectResponse
    {
        $user = Auth::user();

        if ($user === null) {
            abort(403);
        }

        $hasAccess = $user->stores()->wherePivot('store_id', $store)->exists();

        if (! $hasAccess) {
            abort(403);
        }

        $request->session()->put('current_store_id', $store);

        return redirect('/admin');
    }
}

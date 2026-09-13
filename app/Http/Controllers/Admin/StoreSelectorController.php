<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Minimal store switcher for admins with access to multiple stores.
 * ResolveStore redirects here when no store is selected in the session.
 */
class StoreSelectorController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.store-selector', [
            'stores' => $request->user()->stores()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        $isMember = $request->user()->stores()->where('stores.id', $validated['store_id'])->exists();

        if (! $isMember) {
            abort(403);
        }

        $request->session()->put('current_store_id', $validated['store_id']);

        return redirect()->intended(route('admin.dashboard'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $stock = (string) $request->string('stock', 'all');

        $inventoryItems = InventoryItem::query()
            ->with(['variant.product'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('variant', function ($variantQuery) use ($search): void {
                        $variantQuery->where('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($productQuery) use ($search): void {
                                $productQuery->where('title', 'like', "%{$search}%");
                            });
                    });
                });
            })
            ->when($stock === 'in_stock', function ($query): void {
                $query->whereRaw('(quantity_on_hand - quantity_reserved) > 5');
            })
            ->when($stock === 'low_stock', function ($query): void {
                $query->whereRaw('(quantity_on_hand - quantity_reserved) BETWEEN 1 AND 5');
            })
            ->when($stock === 'out_of_stock', function ($query): void {
                $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0');
            })
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.inventory.index', [
            'inventoryItems' => $inventoryItems,
            'search' => $search,
            'stock' => $stock,
            'policies' => InventoryPolicy::cases(),
        ]);
    }

    public function update(Request $request, InventoryItem $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'policy' => ['required', Rule::in($this->enumValues(InventoryPolicy::class))],
        ]);

        $inventory->update([
            'quantity_on_hand' => $validated['quantity_on_hand'],
            'policy' => $validated['policy'],
        ]);

        return back()->with('status', 'Inventory updated.');
    }
}

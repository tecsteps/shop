<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CartStatus;
use App\Enums\NavigationItemType;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;

abstract class StorefrontController extends Controller
{
    public function __construct(
        protected readonly CurrentStore $currentStore,
    ) {}

    protected function currentStore(): Store
    {
        $store = $this->currentStore->get();

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    /**
     * @return array<string, mixed>
     */
    protected function storefrontViewData(Request $request, array $data = []): array
    {
        $store = $this->currentStore();

        return array_merge([
            'store' => $store,
            'menuItems' => $this->menuItems($store),
            'cartItemCount' => $this->cartItemCount($request, $store),
            'currentCustomer' => $this->customer(),
            'currency' => $store->default_currency,
        ], $data);
    }

    protected function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer ? $customer : null;
    }

    protected function money(int $amount, ?string $currency = null): string
    {
        $currencyCode = strtoupper($currency ?: $this->currentStore()->default_currency);

        return number_format($amount / 100, 2, '.', ',').' '.$currencyCode;
    }

    /**
     * @return SupportCollection<int, array{label:string,url:string}>
     */
    protected function menuItems(Store $store): SupportCollection
    {
        $menu = NavigationMenu::query()
            ->where('store_id', $store->id)
            ->where('handle', 'main-menu')
            ->first();

        if (! $menu) {
            return collect([
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Collections', 'url' => route('storefront.collections.index')],
            ]);
        }

        return $menu->items()
            ->orderBy('position')
            ->get()
            ->map(fn (NavigationItem $item): array => [
                'label' => $item->label,
                'url' => $this->menuItemUrl($item),
            ]);
    }

    protected function menuItemUrl(NavigationItem $item): string
    {
        return match ($item->type) {
            NavigationItemType::Collection => $this->collectionUrl($item->resource_id),
            NavigationItemType::Page => $this->pageUrl($item->resource_id),
            NavigationItemType::Product => $this->productUrl($item->resource_id),
            default => $item->url ?: route('home'),
        };
    }

    protected function collectionUrl(?int $id): string
    {
        if (! $id) {
            return route('storefront.collections.index');
        }

        $collection = Collection::query()->whereKey($id)->first();

        return $collection
            ? route('storefront.collections.show', $collection->handle)
            : route('storefront.collections.index');
    }

    protected function pageUrl(?int $id): string
    {
        if (! $id) {
            return route('home');
        }

        $page = Page::query()->whereKey($id)->first();

        return $page
            ? route('storefront.pages.show', $page->handle)
            : route('home');
    }

    protected function productUrl(?int $id): string
    {
        if (! $id) {
            return route('home');
        }

        $product = Product::query()->whereKey($id)->first();

        return $product
            ? route('storefront.products.show', $product->handle)
            : route('home');
    }

    protected function cartSessionKey(Store $store): string
    {
        return 'cart_id_store_'.$store->id;
    }

    protected function discountSessionKey(Store $store): string
    {
        return 'cart_discount_code_store_'.$store->id;
    }

    protected function cartItemCount(Request $request, Store $store): int
    {
        $customer = $this->customer();

        if ($customer) {
            $customerCart = Cart::query()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->latest('id')
                ->first();

            if ($customerCart) {
                return (int) $customerCart->lines()->sum('quantity');
            }
        }

        $cartId = $request->session()->get($this->cartSessionKey($store));

        if (! $cartId) {
            return 0;
        }

        $cart = Cart::query()
            ->where('store_id', $store->id)
            ->whereKey($cartId)
            ->where('status', CartStatus::Active)
            ->first();

        return $cart ? (int) $cart->lines()->sum('quantity') : 0;
    }

    /**
     * @return list<string>
     */
    protected function formatAddressLines(?array $address): array
    {
        if (! is_array($address) || $address === []) {
            return [];
        }

        $name = trim((string) (($address['first_name'] ?? '').' '.($address['last_name'] ?? '')));
        $cityLine = trim(implode(', ', array_filter([
            $address['zip'] ?? null,
            $address['city'] ?? null,
        ], static fn ($value): bool => is_string($value) && $value !== '')));

        return array_values(array_filter([
            $name ?: null,
            $address['company'] ?? null,
            $address['address1'] ?? null,
            $address['address2'] ?? null,
            $cityLine ?: null,
            $address['country'] ?? null,
            $address['phone'] ?? null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''));
    }
}

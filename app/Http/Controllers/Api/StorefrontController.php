<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Exceptions\CartVersionConflictException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class StorefrontController extends Controller
{
    public function createCart(Request $request, CartService $carts): JsonResponse
    {
        $validated = $request->validate(['currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/']]);
        $customer = auth('customer')->user();
        $cart = $carts->getOrCreateActiveCart($this->store(), $customer);

        if (isset($validated['currency'])) {
            $cart->update(['currency' => strtoupper($validated['currency'])]);
        }

        return response()->json($this->cartPayload($cart->refresh()->load('lines.variant.product', 'lines.variant.inventoryItem')), 201);
    }

    public function showCart(int $cartId): JsonResponse
    {
        return response()->json($this->cartPayload($this->cart($cartId)));
    }

    public function addCartLine(Request $request, int $cartId, CartService $carts): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $variant = ProductVariant::query()->whereHas('product', fn ($query) => $query->where('store_id', $this->store()->id))->findOrFail($validated['variant_id']);
            $cart = $carts->add($this->cart($cartId), $variant, $validated['quantity'], $validated['expected_version'] ?? null);
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'current_version' => $exception->currentVersion], 409);
        }

        return response()->json($this->cartPayload($cart));
    }

    public function updateCartLine(Request $request, int $cartId, int $lineId, CartService $carts): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $cart = $carts->updateQuantity($this->cart($cartId), $lineId, $validated['quantity'], $validated['expected_version'] ?? null);
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'current_version' => $exception->currentVersion], 409);
        }

        return response()->json($this->cartPayload($cart));
    }

    public function deleteCartLine(int $cartId, int $lineId, CartService $carts): JsonResponse
    {
        try {
            $cart = $carts->remove($this->cart($cartId), $lineId);
        } catch (CartVersionConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'current_version' => $exception->currentVersion], 409);
        }

        return response()->json($this->cartPayload($cart));
    }

    public function createCheckout(Request $request, CheckoutService $checkouts): JsonResponse
    {
        $validated = $request->validate(['cart_id' => ['required', 'integer']]);
        $checkout = $checkouts->start($this->cart($validated['cart_id']), auth('customer')->user());
        session()->put('checkout_id', $checkout->id);

        return response()->json($this->checkoutPayload($checkout->load('cart.lines.variant.product')), 201);
    }

    public function showCheckout(int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        return response()->json($this->checkoutPayload($checkouts->recalculate($this->checkout($checkoutId)->load('cart.lines.variant.product'))));
    }

    public function updateAddress(Request $request, int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'shipping_address' => ['required', 'array'],
            'billing_address' => ['sometimes', 'array'],
        ]);
        $checkout = $checkouts->setAddress($this->checkout($checkoutId), $validated['email'], $validated['shipping_address'], $validated['billing_address'] ?? null);

        return response()->json($this->checkoutPayload($checkout->load('cart.lines.variant.product')));
    }

    public function updateShipping(Request $request, int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        $validated = $request->validate(['shipping_method_id' => ['nullable', 'integer']]);
        $checkout = $checkouts->selectShippingMethod($this->checkout($checkoutId), $validated['shipping_method_id'] ?? null);

        return response()->json($this->checkoutPayload($checkout->load('cart.lines.variant.product')));
    }

    public function updatePayment(Request $request, int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        $validated = $request->validate(['payment_method' => ['required', Rule::enum(PaymentMethod::class)]]);
        $checkout = $checkouts->selectPaymentMethod($this->checkout($checkoutId), PaymentMethod::from($validated['payment_method']));

        return response()->json($this->checkoutPayload($checkout->load('cart.lines.variant.product')));
    }

    public function applyDiscount(Request $request, int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:100']]);
        $checkout = $checkouts->applyDiscount($this->checkout($checkoutId), $validated['code']);

        return response()->json($this->checkoutPayload($checkout->load('cart.lines.variant.product')));
    }

    public function removeDiscount(int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        $checkout = $this->checkout($checkoutId);
        $checkout->forceFill(['discount_code' => null])->save();
        $checkout = $checkouts->recalculate($checkout->refresh()->load('cart.lines.variant.product'));

        return response()->json($this->checkoutPayload($checkout));
    }

    public function pay(Request $request, int $checkoutId, CheckoutService $checkouts): JsonResponse
    {
        $validated = $request->validate(['card_number' => ['nullable', 'string', 'max:32']]);
        $order = $checkouts->pay($this->checkout($checkoutId), $validated);
        session()->forget(['cart_id', 'checkout_id']);

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'confirmation_url' => URL::signedRoute('storefront.confirmation', ['checkoutId' => $order->checkout_id]),
            'order_access_token' => hash_hmac('sha256', (string) $order->id, (string) config('app.key')),
        ], 201);
    }

    public function showOrder(string $orderNumber): JsonResponse
    {
        $providedToken = (string) request()->query('token', '');
        $order = Order::query()->with('lines', 'payments', 'fulfillments.lines')->where('order_number', $orderNumber)->firstOrFail();

        if ($providedToken === '' || ! hash_equals(hash_hmac('sha256', (string) $order->id, (string) config('app.key')), $providedToken)) {
            abort(401, 'A valid order access token is required.');
        }

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'email' => $order->email,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at,
            'lines' => $order->lines->map(fn ($line): array => [
                'title_snapshot' => $line->title_snapshot,
                'variant_title' => $line->variant_title_snapshot,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ]),
            'totals' => [
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
            ],
            'shipping_address' => $order->shipping_address_json,
            'fulfillments' => $order->fulfillments,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'sort' => ['sometimes', Rule::in(['relevance', 'price_asc', 'price_desc', 'newest', 'best_selling'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'filters' => ['sometimes', 'string', 'max:4000'],
        ]);
        $queryText = trim($validated['q']);
        $filters = [];

        if (isset($validated['filters'])) {
            $filters = json_decode($validated['filters'], true);

            if (! is_array($filters)) {
                return response()->json(['message' => 'The filters parameter must contain a JSON object.'], 400);
            }
        }

        $query = Product::query()->where('status', 'active')->whereNotNull('published_at');
        $ftsTerms = collect(preg_split('/\s+/u', preg_replace('/[^\pL\pN ]/u', ' ', $queryText) ?? '') ?: [])
            ->filter()->map(static fn (string $term): string => '"'.str_replace('"', '""', $term).'"')->implode(' OR ');
        $ids = $ftsTerms === '' ? [] : DB::table('products_fts')->where('products_fts', 'match', $ftsTerms)->where('store_id', $this->store()->id)->pluck('product_id')->all();

        $query->where(function ($builder) use ($ids, $queryText): void {
            if ($ids !== []) {
                $builder->whereIn('id', $ids)->orWhere('title', 'like', '%'.addcslashes($queryText, '%_\\').'%');
            } else {
                $builder->where('title', 'like', '%'.addcslashes($queryText, '%_\\').'%')
                    ->orWhere('description_html', 'like', '%'.addcslashes($queryText, '%_\\').'%')
                    ->orWhere('vendor', 'like', '%'.addcslashes($queryText, '%_\\').'%');
            }
        });

        if (isset($filters['collection_id'])) {
            $query->whereHas('collections', fn ($builder) => $builder->whereKey((int) $filters['collection_id']));
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $query->whereHas('variants', function ($builder) use ($filters): void {
                $builder->when(isset($filters['price_min']), fn ($variantQuery) => $variantQuery->where('price_amount', '>=', (int) $filters['price_min']))
                    ->when(isset($filters['price_max']), fn ($variantQuery) => $variantQuery->where('price_amount', '<=', (int) $filters['price_max']));
            });
        }

        if (($filters['in_stock'] ?? false) === true) {
            $query->whereHas('variants.inventoryItem', fn ($builder) => $builder->whereRaw('quantity_on_hand > quantity_reserved'));
        }

        if (! empty($filters['vendor'])) {
            $query->where('vendor', $filters['vendor']);
        }

        foreach (array_slice($filters['tags'] ?? [], 0, 10) as $tag) {
            $query->whereJsonContains('tags', (string) $tag);
        }

        match ($validated['sort'] ?? 'relevance') {
            'price_asc' => $query->withMin('variants', 'price_amount')->orderBy('variants_min_price_amount'),
            'price_desc' => $query->withMin('variants', 'price_amount')->orderByDesc('variants_min_price_amount'),
            'newest' => $query->orderByDesc('published_at'),
            'best_selling' => $query->orderByDesc('id'),
            default => $query->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$queryText.'%'])->orderBy('title'),
        };

        $allResults = (clone $query)->withMin('variants', 'price_amount')->get();
        $vendors = $allResults->pluck('vendor')->filter()->countBy()->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])->values();
        $tags = $allResults->flatMap(fn (Product $product): array => $product->tags ?? [])->countBy()->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])->values();
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 24);
        $total = $allResults->count();
        $products = $allResults->forPage($page, $perPage)->values()->load(['variants', 'media']);
        SearchQuery::create(['query' => $queryText, 'results_count' => $total, 'session_id' => session()->getId()]);

        return response()->json([
            'query' => $queryText,
            'results' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'handle' => $product->handle,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'price_amount' => (int) ($product->variants_min_price_amount ?? 0),
                'currency' => $this->store()->default_currency,
                'image_url' => $product->media->first()?->storage_key,
                'in_stock' => $product->variants->contains(fn ($variant): bool => ! $variant->inventoryItem || $variant->inventoryItem->policy === 'continue' || $variant->inventoryItem->quantity_on_hand > $variant->inventoryItem->quantity_reserved),
                'tags' => $product->tags,
            ]),
            'facets' => [
                'vendors' => $vendors,
                'tags' => $tags,
                'price_range' => ['min' => (int) $allResults->min('variants_min_price_amount'), 'max' => (int) $allResults->max('variants_min_price_amount')],
            ],
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:1', 'max:100'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:10']]);
        $text = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 5);
        $products = Product::query()->where('status', 'active')->whereNotNull('published_at')->where('title', 'like', '%'.addcslashes($text, '%_\\').'%')->with(['variants', 'media'])->limit($limit)->get();
        $collections = Collection::query()->where('status', 'active')->where('title', 'like', '%'.addcslashes($text, '%_\\').'%')->limit($limit)->get();
        $suggestions = $products->map(fn (Product $product): array => [
            'type' => 'product',
            'title' => $product->title,
            'handle' => $product->handle,
            'image_url' => $product->media->first()?->storage_key,
            'price_amount' => (int) ($product->variants->min('price_amount') ?? 0),
            'currency' => $this->store()->default_currency,
        ])->concat($collections->map(fn (Collection $collection): array => [
            'type' => 'collection',
            'title' => $collection->title,
            'handle' => $collection->handle,
            'image_url' => null,
        ]))->take($limit)->values();

        return response()->json(['query' => $text, 'suggestions' => $suggestions]);
    }

    public function recordAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', Rule::in(['page_view', 'add_to_cart', 'remove_from_cart', 'checkout_started', 'checkout_completed', 'search'])],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['sometimes', 'array'],
            'events.*.occurred_at' => ['required', 'date', 'after_or_equal:'.now()->subHour()->toIso8601String(), 'before_or_equal:'.now()->addHour()->toIso8601String()],
        ]);
        $accepted = 0;

        foreach ($validated['events'] as $event) {
            $inserted = DB::table('analytics_events')->insertOrIgnore([
                'store_id' => $this->store()->id,
                'customer_id' => auth('customer')->id(),
                'type' => $event['type'],
                'session_id' => $event['session_id'],
                'client_event_id' => $event['client_event_id'],
                'payload' => json_encode($event['properties'] ?? [], JSON_THROW_ON_ERROR),
                'created_at' => $event['occurred_at'],
            ]);

            $accepted += $inserted;
        }

        return response()->json(['accepted' => $accepted, 'rejected' => count($validated['events']) - $accepted], 202);
    }

    private function store(): Store
    {
        $store = app('current_store');
        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function cart(int $id): Cart
    {
        $cart = Cart::query()->with('lines.variant.product', 'lines.variant.inventoryItem')->findOrFail($id);
        $customerId = auth('customer')->id();

        if ($customerId !== null) {
            abort_unless((int) $cart->customer_id === (int) $customerId, 404);
        } else {
            abort_unless($cart->customer_id === null && (int) session('cart_id') === (int) $cart->id, 404);
        }

        return $cart;
    }

    private function checkout(int $id): Checkout
    {
        $checkout = Checkout::query()->where('store_id', $this->store()->id)->findOrFail($id);
        $cart = $checkout->cart;

        if ($cart->customer_id !== auth('customer')->id() || (! auth('customer')->check() && (int) session('cart_id') !== (int) $cart->id)) {
            abort(404);
        }

        return $checkout;
    }

    /** @return array<string, mixed> */
    private function cartPayload(Cart $cart): array
    {
        $cart->loadMissing('lines.variant.product');
        $subtotal = (int) $cart->lines->sum(fn ($line): int => $line->unit_price_amount * $line->quantity);

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status,
            'lines' => $cart->lines->map(fn ($line): array => [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_id' => $line->variant?->product_id,
                'title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->title,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->unit_price_amount * $line->quantity,
            ])->values(),
            'totals' => ['subtotal' => $subtotal, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => $subtotal],
        ];
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(Checkout $checkout): array
    {
        return [
            'id' => $checkout->id,
            'cart_id' => $checkout->cart_id,
            'status' => $checkout->status,
            'email' => $checkout->email,
            'shipping_address' => $checkout->shipping_address_json,
            'billing_address' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'payment_method' => $checkout->payment_method,
            'discount_code' => $checkout->discount_code,
            'totals' => [
                'subtotal' => $checkout->subtotal_amount,
                'discount' => $checkout->discount_amount,
                'shipping' => $checkout->shipping_amount,
                'tax' => $checkout->tax_amount,
                'total' => $checkout->total_amount,
                'currency' => $checkout->cart?->currency,
            ],
        ];
    }
}

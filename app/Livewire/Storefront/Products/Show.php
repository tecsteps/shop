<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Product;
use App\Services\CartService;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    use ResolvesStore;

    public string $handle = '';

    /** @var array<string, string> option name => selected value */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public int $selectedImage = 0;

    public ?string $notice = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $product = $this->product();

        abort_if($product === null, 404);

        foreach ($product->productOptions as $option) {
            $defaultValue = $this->defaultValueForOption($product, $option->getKey());

            if ($defaultValue !== null) {
                $this->selectedOptions[$option->name] = $defaultValue;
            }
        }
    }

    public function updatedQuantity(int $value): void
    {
        $max = $this->maxQuantity();

        if ($value < 1) {
            $this->quantity = 1;
        } elseif ($max !== null && $value > $max) {
            $this->quantity = $max;
        }
    }

    public function updatedSelectedOptions(): void
    {
        $this->quantity = 1;
        $this->notice = null;
    }

    public function selectImage(int $index): void
    {
        $this->selectedImage = $index;
    }

    public function incrementQuantity(): void
    {
        $max = $this->maxQuantity();
        $this->quantity++;

        if ($max !== null && $this->quantity > $max) {
            $this->quantity = $max;
        }
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function maxQuantity(): ?int
    {
        $variant = $this->selectedVariant();

        if ($variant === null) {
            return 1;
        }

        return app(StorefrontCatalog::class)->availableQuantity($variant);
    }

    public function selectedVariant(): ?\App\Models\ProductVariant
    {
        $product = $this->product();

        if ($product === null) {
            return null;
        }

        $options = $product->productOptions;

        if ($options->isEmpty()) {
            return app(StorefrontCatalog::class)->displayVariant($product);
        }

        foreach ($product->productVariants->where('status', VariantStatus::Active) as $variant) {
            if ($this->variantMatches($product, $variant)) {
                return $variant;
            }
        }

        return null;
    }

    public function addToCart(): void
    {
        $variant = $this->selectedVariant();

        if ($variant === null) {
            $this->notice = 'Please select all options.';

            return;
        }

        $max = app(StorefrontCatalog::class)->availableQuantity($variant);

        if ($max !== null && $this->quantity > $max) {
            $this->notice = 'Only '.$max.' left in stock.';

            return;
        }

        $store = $this->store();
        $customer = Auth::guard('customer')->user();
        $carts = app(CartService::class);

        try {
            $cart = $carts->getOrCreateForSession($store, $customer);
            $carts->addLine($cart, $variant->getKey(), $this->quantity);
        } catch (InsufficientInventoryException) {
            $this->notice = 'This product is currently out of stock';

            return;
        } catch (ValidationException $exception) {
            $this->notice = collect($exception->errors())->flatten()->first() ?? 'Unable to add to cart.';

            return;
        }

        $this->notice = 'Added to cart';
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $product = $this->product();

        abort_if($product === null, 404);

        $store = $this->store();
        $catalog = app(StorefrontCatalog::class);
        $variant = $this->selectedVariant();
        $currency = $variant?->currency ?? $store->default_currency;
        $collection = $product->collections->first();
        $available = $variant !== null ? $catalog->availableQuantity($variant) : null;
        $inventoryItem = $variant?->inventoryItem;
        $isBackorder = $inventoryItem !== null
            && $inventoryItem->policy === \App\Enums\InventoryPolicy::Continue
            && ($inventoryItem->quantity_on_hand - $inventoryItem->quantity_reserved) <= 0;

        return view('livewire.storefront.products.show', [
            'store' => $store,
            'product' => $product,
            'collection' => $collection,
            'variant' => $variant,
            'currency' => $currency,
            'available' => $available,
            'isBackorder' => $isBackorder,
            'price' => $catalog,
        ])->layout('storefront.layouts.app', [
            'title' => $product->title.' - '.$store->name,
            'metaDescription' => mb_substr(trim(strip_tags((string) $product->description_html)) ?: $product->title, 0, 160),
        ]);
    }

    protected function product(): ?Product
    {
        return Product::query()
            ->where('store_id', $this->store()->getKey())
            ->where('handle', $this->handle)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with([
                'productOptions.productOptionValues',
                'productVariants' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'productVariants.inventoryItem',
                'productVariants.optionValues',
                'productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'collections',
            ])
            ->first();
    }

    protected function defaultValueForOption(Product $product, int $optionId): ?string
    {
        $default = $product->productVariants->firstWhere('is_default', true) ?? $product->productVariants->first();

        $match = $default?->optionValues->firstWhere('product_option_id', $optionId);

        return $match?->value;
    }

    protected function variantMatches(Product $product, \App\Models\ProductVariant $variant): bool
    {
        foreach ($product->productOptions as $option) {
            $selected = $this->selectedOptions[$option->name] ?? null;

            if ($selected === null) {
                return false;
            }

            $has = $variant->optionValues
                ->where('product_option_id', $option->getKey())
                ->contains('value', $selected);

            if (! $has) {
                return false;
            }
        }

        return true;
    }
}

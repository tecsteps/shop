<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\InventoryPolicy;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public string $handle;

    public int $storeId;

    /**
     * @var array<string, string>
     */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
        $this->storeId = $this->store()->getKey();

        $variant = $this->product()->variants->firstWhere('is_default', true)
            ?? $this->product()->variants->first();

        if ($variant instanceof ProductVariant) {
            $this->selectedOptions = $variant->optionValues
                ->mapWithKeys(fn (ProductOptionValue $value): array => [$value->option->name => $value->value])
                ->all();
        }
    }

    public function selectOption(string $optionName, string $value): void
    {
        $this->selectedOptions[$optionName] = $value;
        $this->quantity = 1;
    }

    public function increaseQuantity(): void
    {
        $variant = $this->selectedVariant();
        $max = $variant?->inventoryItem?->policy === InventoryPolicy::Deny
            ? max(1, $variant->inventoryItem->availableQuantity())
            : null;

        if ($max === null || $this->quantity < $max) {
            $this->quantity++;
        }
    }

    public function decreaseQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToCart(): void
    {
        $store = $this->store();
        $variant = $this->selectedVariant();

        if (! $variant instanceof ProductVariant || ! $this->canAddToCart()) {
            return;
        }

        $customer = Auth::guard('customer')->user();
        $customer = $customer instanceof Customer ? $customer : null;

        app(CartService::class)->addLine(
            app(CartService::class)->getOrCreateForSession($store, $customer),
            $variant->getKey(),
            $this->quantity,
        );

        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'success', message: __('Added to cart'));
    }

    public function store(): Store
    {
        if (isset($this->storeId)) {
            $store = Store::query()->findOrFail($this->storeId);
            app()->instance('current_store', $store);

            return $store;
        }

        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    public function product(): Product
    {
        $this->store();

        return Product::query()
            ->with(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'collections'])
            ->where('handle', $this->handle)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->firstOrFail();
    }

    public function selectedVariant(): ?ProductVariant
    {
        $product = $this->product();

        if ($product->options->isEmpty()) {
            return $product->variants->first();
        }

        return $product->variants->first(function (ProductVariant $variant): bool {
            $options = $variant->optionValues
                ->mapWithKeys(fn (ProductOptionValue $value): array => [$value->option->name => $value->value])
                ->all();

            return $options === $this->selectedOptions;
        });
    }

    public function canAddToCart(): bool
    {
        $variant = $this->selectedVariant();

        if (! $variant instanceof ProductVariant || ! $variant->isPurchasable()) {
            return false;
        }

        $inventory = $variant->inventoryItem;

        return $inventory?->policy === InventoryPolicy::Continue
            || ($inventory?->availableQuantity() ?? 0) >= $this->quantity;
    }

    /**
     * @return array{message: string, class: string}
     */
    public function stockState(): array
    {
        $variant = $this->selectedVariant();
        $inventory = $variant?->inventoryItem;

        if (! $inventory) {
            return ['message' => 'Unavailable', 'class' => 'text-red-700 dark:text-red-300'];
        }

        if ($inventory->policy === InventoryPolicy::Continue && $inventory->availableQuantity() <= 0) {
            return ['message' => 'Available on backorder', 'class' => 'text-blue-700 dark:text-blue-300'];
        }

        if ($inventory->availableQuantity() <= 0) {
            return ['message' => 'Out of stock', 'class' => 'text-red-700 dark:text-red-300'];
        }

        if ($inventory->availableQuantity() <= 10) {
            return ['message' => "Only {$inventory->availableQuantity()} left in stock", 'class' => 'text-amber-700 dark:text-amber-300'];
        }

        return ['message' => 'In stock', 'class' => 'text-green-700 dark:text-green-300'];
    }

    public function render(): mixed
    {
        $product = $this->product();

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'selectedVariant' => $this->selectedVariant(),
            'stockState' => $this->stockState(),
        ])->layout('layouts.storefront', [
            'title' => $product->title,
        ]);
    }
}

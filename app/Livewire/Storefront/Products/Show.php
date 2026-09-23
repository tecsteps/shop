<?php

namespace App\Livewire\Storefront\Products;

use App\Livewire\Storefront\Concerns\DispatchesStorefrontAnalytics;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class Show extends Component
{
    use DispatchesStorefrontAnalytics;

    public string $handle = '';

    /** @var array<string, string> */
    public array $selectedOptions = [];

    public ?int $selectedVariantId = null;

    public int $selectedImageIndex = 0;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
        $default = $this->productModel()->variants->firstWhere('is_default', true) ?? $this->productModel()->variants->first();

        if ($default) {
            $this->selectedVariantId = $default->id;

            foreach ($default->optionValues as $value) {
                $this->selectedOptions[$value->option->name] = $value->value;
            }
        }
    }

    public function updatedSelectedOptions(): void
    {
        $this->selectedVariantId = $this->selectedVariant()?->id;
    }

    public function selectImage(int $index): void
    {
        $this->selectedImageIndex = min(max(0, $index), max(0, $this->productModel()->media->count() - 1));
    }

    public function decreaseQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function increaseQuantity(): void
    {
        $this->quantity = min(9999, $this->quantity + 1);
    }

    public function addToCart(CartService $carts): void
    {
        Validator::make(['quantity' => $this->quantity], ['quantity' => ['required', 'integer', 'min:1', 'max:9999']])->validate();
        $variant = $this->selectedVariant();

        if (! $variant) {
            $this->addError('variant', 'Choose an available product option.');

            return;
        }

        $carts->add($carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user()), $variant, $this->quantity);
        $this->dispatchStorefrontAnalytics('add_to_cart', [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => $this->quantity,
            'price_amount' => (int) $variant->price_amount,
            'currency' => $variant->currency,
        ]);
        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
        session()->flash('status', 'Added to cart.');
    }

    public function quickAdd(int $variantId, CartService $carts): void
    {
        $variant = ProductVariant::query()->whereHas('product', fn ($query) => $query->where('status', 'active')->whereNotNull('published_at'))->findOrFail($variantId);
        $carts->add($carts->getOrCreateActiveCart(app('current_store'), auth('customer')->user()), $variant, 1);
        $this->dispatchStorefrontAnalytics('add_to_cart', [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'price_amount' => (int) $variant->price_amount,
            'currency' => $variant->currency,
        ]);
        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }

    public function product(): array
    {
        $product = $this->productModel();
        $selectedVariant = $this->selectedVariant();
        $variants = $product->variants->map(function (ProductVariant $variant): array {
            $item = $variant->inventoryItem;
            $data = $variant->toArray();
            $data['inventory_quantity'] = $item ? max(0, $item->quantity_on_hand - $item->quantity_reserved) : null;
            $data['inventory_policy'] = $item?->policy ?? 'deny';
            $data['title'] = $variant->title;

            return $data;
        });
        $optionRows = $product->options->map(fn ($option): array => [
            'name' => $option->name,
            'values' => $option->values->pluck('value')->all(),
        ]);
        $media = $product->media->map(fn ($image): array => [
            'id' => $image->id,
            'url' => $image->url,
            'thumbnail_url' => $image->thumbnail_url,
            'alt' => $image->alt_text,
        ]);
        $available = $variants->filter(fn (array $variant): bool => $variant['inventory_quantity'] === null || $variant['inventory_quantity'] > 0 || $variant['inventory_policy'] === 'continue');

        return [
            ...$product->toArray(),
            'options' => $optionRows->all(),
            'variants' => $variants->all(),
            'media' => $media->all(),
            'description_html' => app(HtmlSanitizer::class)->sanitize($product->description_html),
            'price_amount' => (int) ($selectedVariant?->price_amount ?? $product->price_amount),
            'compare_at_amount' => $selectedVariant?->compare_at_amount ?? $product->compare_at_amount,
            'currency' => $selectedVariant?->currency ?? $product->currency,
            'sold_out' => $available->isEmpty(),
            'inventory_quantity' => $selectedVariant?->inventoryItem ? max(0, $selectedVariant->inventoryItem->quantity_on_hand - $selectedVariant->inventoryItem->quantity_reserved) : null,
            'inventory_policy' => $selectedVariant?->inventoryItem?->policy ?? 'deny',
        ];
    }

    public function render(): mixed
    {
        return view('storefront.product', ['product' => $this->product(), 'selectedVariant' => $this->selectedVariant()])->layout('storefront.layouts.app');
    }

    private function productModel(): Product
    {
        return Product::query()
            ->where('handle', $this->handle)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->with(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'media', 'collections'])
            ->firstOrFail();
    }

    private function selectedVariant(): ?ProductVariant
    {
        $product = $this->productModel();

        if ($this->selectedVariantId !== null) {
            $selected = $product->variants->firstWhere('id', $this->selectedVariantId);

            if ($selected) {
                return $selected;
            }
        }

        foreach ($product->variants as $variant) {
            $values = $variant->optionValues->mapWithKeys(fn ($value): array => [$value->option->name => $value->value])->all();

            if ($values === $this->selectedOptions) {
                return $variant;
            }
        }

        return $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    }
}

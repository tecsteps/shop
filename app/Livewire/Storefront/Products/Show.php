<?php

namespace App\Livewire\Storefront\Products;

use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use InteractsWithStore;

    public string $handle = '';

    /** @var array<int, int> option id => option value id */
    public array $selectedOptions = [];

    public int $quantity = 1;

    public ?string $addToCartError = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $defaultVariant = $this->product->variants
            ->firstWhere('is_default', true)
            ?? $this->product->variants->where('status', 'active')->first();

        if ($defaultVariant) {
            foreach ($defaultVariant->optionValues as $value) {
                $this->selectedOptions[$value->product_option_id] = $value->id;
            }
        }

        View::share([
            'title' => $this->product->title.' - '.$this->store()->name,
            'metaDescription' => mb_substr(strip_tags((string) $this->product->description_html), 0, 160),
            'og' => $this->ogData(),
        ]);
    }

    public function selectOption(int $optionId, int $valueId): void
    {
        $this->selectedOptions[$optionId] = $valueId;
        $this->quantity = 1;
        $this->addToCartError = null;
    }

    public function incrementQuantity(): void
    {
        $max = $this->maxQuantity;

        if ($max !== null && $this->quantity >= $max) {
            return;
        }

        $this->quantity++;
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToCart(): void
    {
        $variant = $this->selectedVariant;

        if (! $variant) {
            $this->addToCartError = 'Please select all options.';

            return;
        }

        $inventory = $variant->inventoryItem;

        if ($inventory && $inventory->policy === 'deny'
            && ($inventory->quantity_on_hand - $inventory->quantity_reserved) < $this->quantity) {
            $this->addToCartError = 'This product is currently out of stock.';

            return;
        }

        try {
            app(CartService::class)->addLine($this->sessionCart(), $variant->id, $this->quantity);
        } catch (InsufficientInventoryException) {
            $this->addToCartError = 'This product is currently out of stock.';

            return;
        }

        $cart = $this->sessionCart()->fresh()->load('lines');

        $this->addToCartError = null;
        $this->dispatch('cart-updated', cartId: $cart->id, itemCount: (int) $cart->lines->sum('quantity'));
    }

    #[Computed]
    public function product(): Product
    {
        return Product::where('handle', $this->handle)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->with([
                'variants.inventoryItem',
                'variants.optionValues.option',
                'options.values',
                'media' => fn ($media) => $media->where('status', 'ready')->orderBy('position'),
            ])
            ->firstOrFail();
    }

    #[Computed]
    public function gallery(): SupportCollection
    {
        return $this->product->media->where('type', 'image')->values();
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        if (count($this->selectedOptions) !== $this->product->options->count()) {
            return null;
        }

        return $this->product->variants
            ->where('status', 'active')
            ->first(fn (ProductVariant $variant) => $this->variantMatches($variant, $this->selectedOptions));
    }

    #[Computed]
    public function price(): int
    {
        return $this->selectedVariant?->price_amount
            ?? $this->product->variants->where('status', 'active')->min('price_amount')
            ?? 0;
    }

    #[Computed]
    public function compareAt(): ?int
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->compare_at_amount;
        }

        $min = $this->product->variants->where('status', 'active')->min('price_amount');
        $variant = $this->product->variants->where('status', 'active')->firstWhere('price_amount', $min);

        return $variant?->compare_at_amount;
    }

    #[Computed]
    public function currency(): string
    {
        return $this->selectedVariant?->currency
            ?? $this->product->variants->first()?->currency
            ?? $this->store()->default_currency;
    }

    /**
     * @return array{tone: string, text: string}
     */
    #[Computed]
    public function stockMessage(): array
    {
        $variant = $this->selectedVariant;

        if (! $variant) {
            return ['tone' => 'muted', 'text' => 'Select options to check availability'];
        }

        $inventory = $variant->inventoryItem;

        if (! $inventory) {
            return ['tone' => 'success', 'text' => 'In stock'];
        }

        $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

        if ($inventory->policy === 'continue') {
            if ($available > 0) {
                return ['tone' => 'success', 'text' => 'In stock'];
            }

            return ['tone' => 'info', 'text' => 'Available on backorder'];
        }

        if ($available > 10) {
            return ['tone' => 'success', 'text' => 'In stock'];
        }

        if ($available > 0) {
            return ['tone' => 'warning', 'text' => "Only {$available} left in stock"];
        }

        return ['tone' => 'danger', 'text' => 'Out of stock'];
    }

    #[Computed]
    public function isSoldOut(): bool
    {
        $variant = $this->selectedVariant;

        if (! $variant) {
            return false;
        }

        $inventory = $variant->inventoryItem;

        return $inventory
            && $inventory->policy === 'deny'
            && ($inventory->quantity_on_hand - $inventory->quantity_reserved) <= 0;
    }

    #[Computed]
    public function maxQuantity(): ?int
    {
        $variant = $this->selectedVariant;

        if (! $variant) {
            return null;
        }

        $inventory = $variant->inventoryItem;

        if ($inventory && $inventory->policy === 'deny') {
            $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

            return max(0, $available);
        }

        return null;
    }

    /**
     * @return array<int, bool> option value id => available
     */
    #[Computed]
    public function optionAvailability(): array
    {
        $result = [];

        foreach ($this->product->options as $option) {
            foreach ($option->values as $value) {
                $required = array_values($this->selectedOptions);
                $required[] = $value->id;

                $result[$value->id] = $this->product->variants
                    ->where('status', 'active')
                    ->contains(fn (ProductVariant $variant) => $this->variantContainsValues($variant, $required));
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function ogData(): array
    {
        $image = $this->gallery->first();

        return [
            'title' => $this->product->title,
            'description' => mb_substr(strip_tags((string) $this->product->description_html), 0, 160),
            'type' => 'product',
            'image' => $image ? Storage::url($image->storage_key) : null,
            'price_amount' => $this->price,
            'price_currency' => $this->currency,
        ];
    }

    /**
     * @param  array<int, int>  $selected
     */
    private function variantMatches(ProductVariant $variant, array $selected): bool
    {
        $variantIds = $variant->optionValues->pluck('id')->sort()->values()->all();
        $selectedIds = collect($selected)->sort()->values()->all();

        return $variantIds === $selectedIds;
    }

    /**
     * @param  list<int>  $valueIds
     */
    private function variantContainsValues(ProductVariant $variant, array $valueIds): bool
    {
        $variantIds = $variant->optionValues->pluck('id')->all();

        foreach ($valueIds as $id) {
            if (! in_array($id, $variantIds, true)) {
                return false;
            }
        }

        return true;
    }
}

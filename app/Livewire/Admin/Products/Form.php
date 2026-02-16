<?php

namespace App\Livewire\Admin\Products;

use App\Livewire\Admin\Concerns\HasStoreForm;
use App\Models\Product;
use App\Models\ProductOptionValue;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.admin.layout', ['title' => 'Product'])]
class Form extends Component
{
    use HasStoreForm;
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $description_html = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $product_type = '';

    public string $tags = '';

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array{price: string, compare_at_price: string, sku: string, barcode: string, weight: string, quantity: int, option_values: array<string>}> */
    public array $variants = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newMedia = [];

    protected function modelProperty(): string
    {
        return 'product';
    }

    protected function modelClass(): string
    {
        return Product::class;
    }

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->authorize('update', $product);
            $this->product = $product;
            $this->title = $product->title;
            $this->description_html = $product->description_html ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->product_type = $product->product_type ?? '';
            $this->tags = is_array($product->tags) ? implode(', ', $product->tags) : '';

            $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues']);

            foreach ($product->options as $option) {
                $this->options[] = [
                    'name' => $option->name,
                    'values' => $option->values->pluck('value')->implode(', '),
                ];
            }

            foreach ($product->variants as $variant) {
                $this->variants[] = [
                    'price' => (string) ($variant->price_amount / 100),
                    'compare_at_price' => $variant->compare_at_amount ? (string) ($variant->compare_at_amount / 100) : '',
                    'sku' => $variant->sku ?? '',
                    'barcode' => $variant->barcode ?? '',
                    'weight' => $variant->weight_g ? (string) $variant->weight_g : '',
                    'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                    'option_values' => $variant->optionValues->pluck('value')->all(),
                ];
            }

            if (empty($this->variants)) {
                $this->addDefaultVariant();
            }
        } else {
            $this->addDefaultVariant();
        }
    }

    public function addOption(): void
    {
        $this->options[] = ['name' => '', 'values' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->generateVariants();
    }

    public function generateVariants(): void
    {
        $optionSets = [];
        foreach ($this->options as $option) {
            $values = array_map('trim', explode(',', $option['values']));
            $values = array_filter($values);
            if (! empty($values)) {
                $optionSets[] = $values;
            }
        }

        if (empty($optionSets)) {
            $this->variants = [];
            $this->addDefaultVariant();

            return;
        }

        $combinations = $this->cartesian($optionSets);
        $this->variants = [];

        foreach ($combinations as $combo) {
            $comboArray = is_array($combo) ? $combo : [$combo];
            $this->variants[] = [
                'price' => '',
                'compare_at_price' => '',
                'sku' => '',
                'barcode' => '',
                'weight' => '',
                'quantity' => 0,
                'option_values' => $comboArray,
            ];
        }
    }

    public function save(): void
    {
        $store = $this->authorizeAndValidate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,active,archived'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $productData = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => Str::slug($this->title),
            'description_html' => sanitize_html($this->description_html),
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->product_type ?: null,
            'tags' => $this->tags ? array_map('trim', explode(',', $this->tags)) : [],
            'published_at' => $this->status === 'active' ? now() : null,
        ];

        if ($this->product) {
            $this->product->update($productData);
            $product = $this->product;
        } else {
            $product = Product::query()->create($productData);
        }

        // Sync options
        $product->options()->delete();
        foreach ($this->options as $pos => $opt) {
            if (! $opt['name']) {
                continue;
            }
            $option = $product->options()->create(['name' => $opt['name'], 'position' => $pos]);
            $values = array_filter(array_map('trim', explode(',', $opt['values'])));
            foreach ($values as $vPos => $val) {
                $option->values()->create(['value' => $val, 'position' => $vPos]);
            }
        }

        // Sync variants
        $product->variants()->delete();
        foreach ($this->variants as $pos => $v) {
            $variant = $product->variants()->create([
                'sku' => $v['sku'] ?: null,
                'barcode' => $v['barcode'] ?: null,
                'price_amount' => (int) (((float) $v['price']) * 100),
                'compare_at_amount' => $v['compare_at_price'] ? (int) (((float) $v['compare_at_price']) * 100) : null,
                'currency' => $store->currency ?? 'USD',
                'weight_g' => $v['weight'] ? (int) $v['weight'] : null,
                'is_default' => $pos === 0,
                'position' => $pos,
            ]);

            $variant->inventoryItem()->updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => $v['quantity'] ?? 0,
                    'quantity_reserved' => 0,
                ]
            );

            // Attach option values
            if (! empty($v['option_values'])) {
                $optionValueIds = [];
                foreach ($v['option_values'] as $ov) {
                    $pov = ProductOptionValue::query()
                        ->whereHas('option', fn ($q) => $q->where('product_id', $product->id))
                        ->where('value', $ov)
                        ->first();
                    if ($pov) {
                        $optionValueIds[] = $pov->id;
                    }
                }
                $variant->optionValues()->sync($optionValueIds);
            }
        }

        // Handle media uploads
        foreach ($this->newMedia as $file) {
            $path = $file->store('products', 'public');
            $product->media()->create([
                'store_id' => $store->id,
                'type' => 'image',
                'url' => $path,
                'alt' => $this->title,
                'position' => $product->media()->count(),
            ]);
        }
        $this->newMedia = [];

        session()->flash('success', $this->product ? 'Product updated.' : 'Product created.');
        $this->redirect(route('admin.products.index'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.products.form');
    }

    private function addDefaultVariant(): void
    {
        $this->variants[] = [
            'price' => '',
            'compare_at_price' => '',
            'sku' => '',
            'barcode' => '',
            'weight' => '',
            'quantity' => 0,
            'option_values' => [],
        ];
    }

    /**
     * @param  array<int, array<int, string>>  $arrays
     * @return array<int, array<int, string>>
     */
    private function cartesian(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $array) {
            $temp = [];
            foreach ($result as $r) {
                foreach ($array as $item) {
                    $temp[] = array_merge($r, [$item]);
                }
            }
            $result = $temp;
        }

        return $result;
    }
}

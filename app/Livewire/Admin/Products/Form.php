<?php

namespace App\Livewire\Admin\Products;

use App\Enums\MediaStatus;
use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    use WithFileUploads;

    public string $mode = 'create';

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $mediaUploads = [];

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
        ];
    }

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->mode = 'edit';
            $this->product = $product;
            $this->title = $product->title;
            $this->descriptionHtml = $product->description_html ?? '';
            /** @var \App\Enums\ProductStatus $productStatus */
            $productStatus = $product->status;
            $this->status = $productStatus->value;
            $this->vendor = $product->vendor ?? '';
            $this->productType = $product->product_type ?? '';
            /** @var array<int, string>|null $tags */
            $tags = $product->tags;
            $this->tags = is_array($tags) ? implode(', ', $tags) : '';

            /** @var array<int, array{name: string, values: string}> $options */
            $options = $product->options->map(fn (ProductOption $option): array => [
                'name' => $option->name,
                'values' => $option->values->pluck('value')->implode(', '),
            ])->toArray();
            $this->options = $options;
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
    }

    public function save(): void
    {
        $this->validate();

        $tagsArray = $this->tags !== ''
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $data = [
            'title' => $this->title,
            'handle' => Str::slug($this->title).'-'.Str::random(4),
            'description_html' => $this->descriptionHtml,
            'status' => $this->status,
            'vendor' => $this->vendor,
            'product_type' => $this->productType,
            'tags' => $tagsArray,
        ];

        if ($this->status === 'active' && (! $this->product || ! $this->product->published_at)) {
            $data['published_at'] = now();
        }

        if ($this->mode === 'edit' && $this->product) {
            $this->product->update($data);
            $product = $this->product;
        } else {
            $product = Product::create($data);
        }

        $this->syncOptions($product);
        $this->handleMediaUploads($product);

        $this->redirect(route('admin.products.edit', $product), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.products.form');
    }

    private function syncOptions(Product $product): void
    {
        if ($this->mode === 'edit') {
            $product->options()->delete();
        }

        foreach ($this->options as $position => $optionData) {
            if (empty($optionData['name'])) {
                continue;
            }

            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $optionData['name'],
                'position' => $position,
            ]);

            $values = array_map('trim', explode(',', $optionData['values']));
            foreach ($values as $valPosition => $value) {
                if ($value === '') {
                    continue;
                }
                ProductOptionValue::create([
                    'product_option_id' => $option->id,
                    'value' => $value,
                    'position' => $valPosition,
                ]);
            }
        }

        $this->generateVariantsFromOptions($product);
    }

    private function generateVariantsFromOptions(Product $product): void
    {
        $optionSets = $product->options()->with('values')->get();

        if ($optionSets->isEmpty()) {
            if ($product->variants()->count() === 0) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => Str::upper(Str::random(8)),
                    'price_amount' => 0,
                    'is_default' => true,
                    'position' => 0,
                    'status' => VariantStatus::Active,
                ]);
            }

            return;
        }

        // Only generate variants when creating new products or when options changed
        if ($this->mode === 'edit') {
            $product->variants()->delete();
        }

        /** @var array<int, array<int, array{id: int, product_option_id: int, value: string, position: int}>> $valueSets */
        $valueSets = $optionSets->map(fn ($option) => $option->values->toArray())->toArray();
        $combinations = $this->cartesianProduct($valueSets);

        foreach ($combinations as $position => $combination) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => Str::upper(Str::random(8)),
                'price_amount' => 0,
                'is_default' => $position === 0,
                'position' => $position,
                'status' => VariantStatus::Active,
            ]);

            foreach ($combination as $optionValue) {
                /** @var array{id: int} $optionValue */
                $variant->optionValues()->attach($optionValue['id']);
            }
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $sets
     * @return array<int, array<int, mixed>>
     */
    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }

        $result = [[]];
        foreach ($sets as $set) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($set as $item) {
                    $newResult[] = array_merge($existing, [$item]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    private function handleMediaUploads(Product $product): void
    {
        foreach ($this->mediaUploads as $file) {
            $path = $file->store('product-media', 'public');

            ProductMedia::create([
                'product_id' => $product->id,
                'type' => 'image',
                'storage_key' => (string) $path,
                'alt_text' => $this->title,
                'mime_type' => (string) $file->getMimeType(),
                'byte_size' => (int) $file->getSize(),
                'position' => $product->media()->count(),
                'status' => MediaStatus::Ready,
            ]);
        }

        $this->mediaUploads = [];
    }
}

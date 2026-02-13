<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public string $mode = 'create';

    public ?Collection $collection = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    /** @var list<int> */
    public array $selectedProducts = [];

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ];
    }

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->mode = 'edit';
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->descriptionHtml = $collection->description_html ?? '';
            /** @var \App\Enums\CollectionStatus $collectionStatus */
            $collectionStatus = $collection->status;
            $this->status = $collectionStatus->value;
            /** @var list<int> $productIds */
            $productIds = $collection->products()->pluck('products.id')->toArray();
            $this->selectedProducts = $productIds;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'handle' => Str::slug($this->title).'-'.Str::random(4),
            'description_html' => $this->descriptionHtml,
            'status' => $this->status,
        ];

        if ($this->mode === 'edit' && $this->collection) {
            $this->collection->update($data);
            $collection = $this->collection;
        } else {
            $collection = Collection::create($data);
        }

        $collection->products()->sync($this->selectedProducts);

        $this->redirect(route('admin.collections.edit', $collection), navigate: true);
    }

    public function render(): View
    {
        $availableProducts = Product::query()->latest()->limit(50)->get();

        return view('livewire.admin.collections.form', [
            'availableProducts' => $availableProducts,
        ]);
    }
}

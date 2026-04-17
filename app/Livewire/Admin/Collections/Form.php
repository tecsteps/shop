<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Collection $collection = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|string')]
    public string $status = 'draft';

    public function mount(?Collection $collection = null): void
    {
        if ($collection?->exists) {
            $this->collection = $collection;
            $this->title = $collection->title;
            /** @var string $description */
            $description = $collection->description ?? '';
            $this->description = $description;
            $this->status = $collection->status->value;
        }
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        if ($this->collection) {
            $this->collection->update([
                'title' => $this->title,
                'description' => $this->description ?: null,
                'status' => $this->status,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Collection updated successfully.');
        } else {
            $handle = app(HandleGenerator::class)->generate($this->title, 'collections', $store->id);

            $collection = Collection::query()->withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handle,
                'description' => $this->description ?: null,
                'status' => $this->status,
            ]);

            $this->redirect(route('admin.collections.edit', $collection), navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form');
    }
}

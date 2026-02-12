<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Create Collection')]
class Create extends Component
{
    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $type = 'manual';

    public string $status = 'draft';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public function updatedTitle(): void
    {
        if ($this->handle === '' || $this->handle === Str::slug($this->title)) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', 'in:manual,automated'],
            'status' => ['required', 'in:draft,active,archived'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $store = app('current_store');

        $collection = Collection::query()->create([
            'store_id' => $store->id,
            'title' => $validated['title'],
            'handle' => $validated['handle'],
            'description_html' => $validated['descriptionHtml'],
            'type' => $validated['type'],
            'status' => $validated['status'],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Collection created.');

        $this->redirect(route('admin.collections.edit', $collection), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.collections.create', [
            'statuses' => CollectionStatus::cases(),
            'types' => CollectionType::cases(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin\Collections;

use App\Actions\SanitizeHtml;
use App\Enums\CollectionStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Collection as ProductCollection;
use App\Support\HandleGenerator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    use UsesAdminStore;

    public ?ProductCollection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public function mount(?ProductCollection $collection = null): void
    {
        $this->collection = $collection?->exists ? $collection : null;

        if ($this->collection === null) {
            return;
        }

        $this->title = $this->collection->title;
        $this->handle = $this->collection->handle;
        $this->descriptionHtml = $this->collection->description_html ?? '';
        $this->status = $this->collection->status->value;
    }

    public function save(HandleGenerator $handles, SanitizeHtml $sanitizeHtml): mixed
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_map(fn (CollectionStatus $status): string => $status->value, CollectionStatus::cases()))],
        ]);

        $payload = [
            'store_id' => $this->currentStore()->id,
            'title' => $validated['title'],
            'handle' => $validated['handle'] ?: $handles->generate($validated['title'], (new ProductCollection)->getTable(), $this->currentStore()->id, $this->collection?->id),
            'description_html' => $sanitizeHtml($validated['descriptionHtml']),
            'status' => $validated['status'],
        ];

        $this->collection = $this->collection === null
            ? ProductCollection::query()->create($payload)
            : tap($this->collection)->update($payload);

        $this->notify('Collection saved.');

        return $this->redirect(route('admin.collections.edit', $this->collection), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form', [
            'statuses' => CollectionStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => $this->collection ? 'Edit collection' : 'Create collection',
        ]);
    }
}

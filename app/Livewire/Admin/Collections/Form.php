<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Support\HandleGenerator;
use Livewire\Component;

class Form extends Component
{
    public ?int $collectionId = null;

    public string $title = '';

    public string $description_html = '';

    public string $type = 'manual';

    public string $status = 'draft';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description_html' => ['nullable', 'string'],
            'type' => ['required', 'in:manual,automated'],
            'status' => ['required', 'in:draft,active,archived'],
        ];
    }

    public function mount(?int $collectionId = null): void
    {
        if ($collectionId) {
            $collection = Collection::findOrFail($collectionId);
            $this->collectionId = $collection->id;
            $this->title = $collection->title;
            $this->description_html = $collection->description_html ?? '';
            $this->type = $collection->type->value;
            $this->status = $collection->status->value;
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $store = app('current_store');

        if ($this->collectionId) {
            $collection = Collection::findOrFail($this->collectionId);
            $collection->update([
                'title' => $this->title,
                'description_html' => $this->description_html ?: null,
                'type' => $this->type,
                'status' => $this->status,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Collection updated.');
        } else {
            $handle = app(HandleGenerator::class)->generate($this->title, 'collections', $store->id);
            $collection = Collection::create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handle,
                'description_html' => $this->description_html ?: null,
                'type' => $this->type,
                'status' => $this->status,
            ]);
            session()->flash('toast', ['type' => 'success', 'message' => 'Collection created.']);

            return redirect()->route('admin.collections.edit', $collection);
        }

        return null;
    }

    public function render(): mixed
    {
        $isEdit = (bool) $this->collectionId;

        return view('livewire.admin.collections.form', [
            'isEdit' => $isEdit,
        ])->layout('layouts.admin.app', [
            'title' => $isEdit ? "Edit {$this->title}" : 'New Collection',
        ]);
    }
}

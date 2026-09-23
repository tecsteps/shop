<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Form extends Component
{
    public ?int $collectionId = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public function mount(?int $collection = null): void
    {
        Gate::authorize('manage-products');

        if ($collection) {
            $saved = Collection::query()->findOrFail($collection);
            $this->collectionId = $saved->id;
            $this->title = $saved->title;
            $this->handle = $saved->handle;
            $this->descriptionHtml = strip_tags($saved->description_html ?? '');
            $this->status = $saved->status;
        }
    }

    public function save(HandleGenerator $handles): mixed
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,active,archived'],
        ]);
        $storeId = app('current_store')->id;
        $attributes = [
            'title' => $this->title,
            'handle' => $handles->generate($this->handle ?: $this->title, 'collections', $storeId, $this->collectionId),
            'description_html' => e($this->descriptionHtml),
            'status' => $this->status,
        ];

        if ($this->collectionId) {
            Collection::query()->findOrFail($this->collectionId)->update($attributes);
        } else {
            Collection::create(['store_id' => $storeId, ...$attributes]);
        }

        session()->flash('status', 'Collection saved.');

        return $this->redirectRoute('admin.collections');
    }

    public function render(): mixed
    {
        return view('livewire.admin.collections.form', ['isEditing' => $this->collectionId !== null])->layout('layouts.admin');
    }
}

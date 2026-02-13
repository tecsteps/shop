<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Collection $collection = null;

    public string $title = '';

    public string $description_html = '';

    public string $status = 'active';

    public string $type = 'manual';

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->authorize('update', Collection::class);
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->description_html = $collection->description_html ?? '';
            $this->status = $collection->status->value;
            $this->type = $collection->type->value;
        } else {
            $this->authorize('create', Collection::class);
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description_html' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'type' => ['required', 'in:manual,automated'],
        ]);

        $store = app('current_store');
        $handleGenerator = app(HandleGenerator::class);

        if ($this->collection) {
            $this->collection->update([
                'title' => $this->title,
                'handle' => $handleGenerator->generate($this->title, 'collections', $store->id, $this->collection->id),
                'description_html' => $this->description_html,
                'status' => $this->status,
                'type' => $this->type,
            ]);

            session()->flash('toast', ['type' => 'success', 'message' => __('Collection updated.')]);
        } else {
            Collection::query()->create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handleGenerator->generate($this->title, 'collections', $store->id),
                'description_html' => $this->description_html,
                'status' => $this->status,
                'type' => $this->type,
            ]);

            session()->flash('toast', ['type' => 'success', 'message' => __('Collection created.')]);
        }

        $this->redirect(route('admin.collections.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form');
    }
}

<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection as CollectionModel;
use App\Support\HandleGenerator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    public ?int $collectionId = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public function mount(?int $collection = null): void
    {
        if ($collection !== null) {
            $model = CollectionModel::query()->findOrFail($collection);
            $this->authorize('view', $model);
            $this->collectionId = (int) $model->getKey();
            $this->title = (string) $model->title;
            $this->handle = (string) $model->handle;
            $this->descriptionHtml = (string) ($model->description_html ?? '');
            $this->status = $model->status->value;

            return;
        }

        $this->authorize('create', CollectionModel::class);
    }

    public function save(): mixed
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:active,archived'],
        ]);

        $storeId = (int) app('current_store')->getKey();

        if ($this->collectionId !== null) {
            $model = CollectionModel::query()->findOrFail($this->collectionId);
            $this->authorize('update', $model);
            $model->title = $this->title;
            $model->description_html = $this->descriptionHtml !== '' ? $this->descriptionHtml : null;
            $model->status = CollectionStatus::from($this->status);

            if ($this->handle !== '') {
                $model->handle = HandleGenerator::unique(CollectionModel::class, $storeId, $this->handle, $model->getKey());
            }

            $model->save();
            session()->flash('status', 'Collection updated.');

            return redirect('/admin/collections/'.$model->getKey().'/edit');
        }

        $this->authorize('create', CollectionModel::class);

        $handle = HandleGenerator::unique(CollectionModel::class, $storeId, $this->handle !== '' ? $this->handle : $this->title);

        $created = new CollectionModel([
            'title' => $this->title,
            'handle' => $handle,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::from($this->status)->value,
        ]);
        $created->store_id = $storeId;
        $created->save();

        session()->flash('status', 'Collection created.');

        return redirect('/admin/collections/'.$created->getKey().'/edit');
    }

    public function render(): View
    {
        return view('livewire.admin.collections.edit');
    }
}

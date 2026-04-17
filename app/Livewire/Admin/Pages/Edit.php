<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\HandleGenerator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    public ?int $pageId = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(?int $page = null): void
    {
        if ($page !== null) {
            $model = Page::query()->findOrFail($page);
            $this->authorize('view', $model);
            $this->pageId = (int) $model->getKey();
            $this->title = (string) $model->title;
            $this->handle = (string) $model->handle;
            $this->bodyHtml = (string) ($model->body_html ?? '');
            $this->status = $model->status->value;

            return;
        }

        $this->authorize('create', Page::class);
    }

    public function save(): mixed
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:'.implode(',', PageStatus::values())],
        ]);

        $storeId = (int) app('current_store')->getKey();

        $data = [
            'title' => $this->title,
            'body_html' => $this->bodyHtml !== '' ? $this->bodyHtml : null,
            'status' => PageStatus::from($this->status)->value,
        ];

        if ($this->pageId !== null) {
            $model = Page::query()->findOrFail($this->pageId);
            $this->authorize('update', $model);

            if ($this->handle !== '') {
                $data['handle'] = HandleGenerator::unique(Page::class, $storeId, $this->handle, $model->getKey());
            }

            if ($this->status === 'published' && $model->published_at === null) {
                $data['published_at'] = now();
            }

            $model->fill($data);
            $model->save();
            session()->flash('status', 'Page updated.');

            return redirect('/admin/pages/'.$model->getKey().'/edit');
        }

        $this->authorize('create', Page::class);
        $data['handle'] = HandleGenerator::unique(Page::class, $storeId, $this->handle !== '' ? $this->handle : $this->title);

        if ($this->status === 'published') {
            $data['published_at'] = now();
        }

        $created = new Page($data);
        $created->store_id = $storeId;
        $created->save();

        session()->flash('status', 'Page created.');

        return redirect('/admin/pages/'.$created->getKey().'/edit');
    }

    public function render(): View
    {
        return view('livewire.admin.pages.edit');
    }
}

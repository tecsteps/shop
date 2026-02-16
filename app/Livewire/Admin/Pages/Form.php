<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Page'])]
class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $content = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->authorize('update', $page);
            $this->page = $page;
            $this->title = $page->title;
            $this->content = $page->content ?? '';
            $this->status = $page->status->value;
        }
    }

    public function save(): void
    {
        if ($this->page) {
            $this->authorize('update', $this->page);
        } else {
            $this->authorize('create', Page::class);
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => Str::slug($this->title),
            'content' => $this->content
                ? strip_tags($this->content, '<p><br><strong><em><ul><ol><li><h2><h3><h4><a><img>')
                : null,
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        if ($this->page) {
            $this->page->update($data);
        } else {
            Page::query()->create($data);
        }

        session()->flash('success', $this->page ? 'Page updated.' : 'Page created.');
        $this->redirect(route('admin.pages.index'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.form');
    }
}

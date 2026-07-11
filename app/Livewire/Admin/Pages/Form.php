<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Form extends AdminComponent
{
    public ?int $pageId = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page === null || ! $page->exists) {
            Gate::authorize('create', Page::class);

            return;
        }
        abort_unless($page->store_id === $this->currentStore()->getKey(), 404);
        Gate::authorize('update', $page);
        $this->pageId = $page->getKey();
        $this->title = $page->title;
        $this->handle = $page->handle;
        $this->bodyHtml = $page->body_html ?? '';
        $this->status = $page->status->value;
    }

    public function updatedTitle(): void
    {
        if ($this->pageId === null) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function save(): void
    {
        Gate::authorize($this->pageId === null ? 'create' : 'update', $this->pageId === null ? Page::class : Page::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->pageId));
        $validated = $this->validate(['title' => ['required', 'string', 'max:255'], 'handle' => ['required', 'string', 'max:255'], 'bodyHtml' => ['nullable', 'string', 'max:65535'], 'status' => ['required', Rule::enum(PageStatus::class)]]);
        $page = $this->pageId === null ? new Page(['store_id' => $this->currentStore()->getKey()]) : Page::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->pageId);
        $page->fill(['title' => $validated['title'], 'handle' => Str::slug($validated['handle']), 'body_html' => $validated['bodyHtml'], 'status' => $validated['status'], 'published_at' => $validated['status'] === 'published' ? ($page->published_at ?? now()) : null])->save();
        $this->pageId = $page->getKey();
        $this->toast('Page saved.');
    }

    public function render()
    {
        return view('livewire.admin.pages.form');
    }
}

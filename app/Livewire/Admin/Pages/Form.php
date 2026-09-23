<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Form extends Component
{
    public ?int $pageId = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(?int $page = null): void
    {
        Gate::authorize('manage-store-settings');

        if ($page) {
            $saved = Page::query()->findOrFail($page);
            $this->pageId = $saved->id;
            $this->title = $saved->title;
            $this->handle = $saved->handle;
            $this->bodyHtml = strip_tags($saved->body_html ?? '');
            $this->status = $saved->status;
        }
    }

    public function save(HandleGenerator $handles): mixed
    {
        Gate::authorize('manage-store-settings');
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);
        $storeId = app('current_store')->id;
        $attributes = [
            'title' => $this->title,
            'handle' => $handles->generate($this->handle ?: $this->title, 'pages', $storeId, $this->pageId),
            'body_html' => e($this->bodyHtml),
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        if ($this->pageId) {
            Page::query()->findOrFail($this->pageId)->update($attributes);
        } else {
            Page::create(['store_id' => $storeId, ...$attributes]);
        }

        session()->flash('status', 'Page saved.');

        return $this->redirectRoute('admin.pages');
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.form', ['isEditing' => $this->pageId !== null])->layout('layouts.admin');
    }
}

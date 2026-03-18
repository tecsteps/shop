<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page?->exists) {
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = $page->body_html ?? '';
            $this->status = $page->status->value;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $store = app('current_store');

        if (! $this->handle) {
            $this->handle = Str::slug($this->title);
        }

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'body_html' => $this->bodyHtml ?: null,
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        if ($this->page?->exists) {
            $this->page->update($data);
            $page = $this->page;
        } else {
            $page = Page::create($data);
        }

        $this->dispatch('toast', type: 'success', message: $this->isEditing
            ? __('Page updated.')
            : __('Page created.')
        );

        $this->redirect(route('admin.pages.edit', $page), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->page?->exists ?? false;
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form');
    }
}

<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Page')]
class Form extends Component
{
    public ?Page $page = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255')]
    public string $handle = '';

    #[Validate('nullable|string|max:65535')]
    public string $bodyHtml = '';

    #[Validate('required|string|in:draft,published,archived')]
    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->authorize('update', $page);
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = $page->body_html ?? '';
            $this->status = $page->status->value;
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->page !== null && $this->page->exists;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            $this->authorize('update', $this->page);
        } else {
            $this->authorize('create', Page::class);
        }

        $store = app('current_store');

        if (empty($this->handle)) {
            $this->handle = Str::slug($this->title);
        }

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'body_html' => $this->bodyHtml ?: null,
            'status' => $this->status,
        ];

        if ($this->isEditing) {
            $this->page->update($data);
        } else {
            $page = Page::create($data);
            $this->page = $page;
        }

        $this->dispatch('toast', type: 'success', message: $this->isEditing ? 'Page updated.' : 'Page created.');

        if (! $this->isEditing) {
            $this->redirect(route('admin.pages.edit', $this->page), navigate: true);
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.pages.form');
    }
}

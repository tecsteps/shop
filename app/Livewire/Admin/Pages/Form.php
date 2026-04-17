<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public string $metaTitle = '';

    public string $metaDescription = '';

    /** @var array<string, list<string>> */
    protected array $rules = [
        'title' => ['required', 'string', 'max:255'],
        'handle' => ['required', 'string', 'max:255'],
        'bodyHtml' => ['nullable', 'string'],
        'status' => ['required', 'string', 'in:draft,published,archived'],
        'metaTitle' => ['nullable', 'string', 'max:255'],
        'metaDescription' => ['nullable', 'string', 'max:500'],
    ];

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = $page->content_html ?? '';
            $this->status = $page->status->value;
            $this->metaTitle = $page->meta_title ?? '';
            $this->metaDescription = $page->meta_description ?? '';
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->page) {
            $this->handle = Str::slug($this->title);
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

        $data = [
            'title' => $this->title,
            'handle' => $this->handle,
            'content_html' => $this->bodyHtml,
            'status' => PageStatus::from($this->status),
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        if ($this->isEditing) {
            $this->page->update($data);
            $this->dispatch('toast', type: 'success', message: 'Page updated successfully.');
        } else {
            $data['store_id'] = app('current_store')->id;
            $this->page = Page::create($data);
            $this->dispatch('toast', type: 'success', message: 'Page created successfully.');
            $this->redirect(route('admin.pages.edit', $this->page), navigate: true);
        }
    }

    public function deletePage(): void
    {
        if ($this->page) {
            $this->page->delete();
            $this->dispatch('toast', type: 'success', message: 'Page deleted.');
            $this->redirect(route('admin.pages.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.admin.pages.form')
            ->layout('layouts.admin', ['title' => $this->isEditing ? "Edit {$this->title}" : 'Create Page']);
    }
}

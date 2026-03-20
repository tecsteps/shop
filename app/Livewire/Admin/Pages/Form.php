<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = $page->body_html ?? '';
            $this->status = $page->status->value;
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->isEditing) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function save(): void
    {
        $storeId = app('current_store')->id;

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'required', 'string', 'max:255',
                Rule::unique('pages', 'handle')
                    ->where('store_id', $storeId)
                    ->ignore($this->page?->id),
            ],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $data = [
            'store_id' => $storeId,
            'title' => $this->title,
            'handle' => $this->handle,
            'body_html' => $this->bodyHtml ?: null,
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now()->toIso8601String() : null,
        ];

        if ($this->page && $this->page->exists) {
            $this->page->update($data);
        } else {
            $this->page = Page::withoutGlobalScopes()->create($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Page saved.');
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->page !== null && $this->page->exists;
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.form')
            ->layout('layouts.admin', [
                'breadcrumbs' => [
                    ['label' => 'Pages', 'url' => route('admin.pages.index')],
                    ['label' => $this->isEditing ? $this->page->title : 'Add page'],
                ],
            ]);
    }
}

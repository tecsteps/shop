<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public ?Page $page = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255')]
    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = $page->body_html ?? '';
            $this->status = $page->status ?? 'draft';
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i');
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->page) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'store_id' => session('store_id'),
            'title' => $this->title,
            'handle' => $this->handle,
            'body_html' => $this->bodyHtml ?: null,
            'status' => $this->status,
            'published_at' => $this->publishedAt ? \Carbon\Carbon::parse($this->publishedAt) : null,
        ];

        if ($this->page) {
            $this->page->update($data);
        } else {
            $this->page = Page::withoutGlobalScopes()->create($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Page saved.');

        if ($this->page->wasRecentlyCreated) {
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.pages.form');
    }
}

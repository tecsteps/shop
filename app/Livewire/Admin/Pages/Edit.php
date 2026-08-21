<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Edit extends Component
{
    public Page $page;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(Page $page): void
    {
        $this->page = $page;
        $this->title = $page->title;
        $this->handle = $page->handle;
        $this->bodyHtml = (string) $page->body_html;
        $this->status = $page->status->value;
    }

    public function save(): void
    {
        $this->authorize('update', $this->page);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
        ]);
        $this->page->update([
            'title' => $data['title'],
            'handle' => $data['handle'],
            'body_html' => $data['bodyHtml'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($this->page->published_at ?? now()) : null,
        ]);
        $this->dispatch('toast', message: 'Page saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form')->layout('layouts.admin');
    }
}

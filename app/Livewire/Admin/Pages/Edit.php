<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Edit Page')]
class Edit extends Component
{
    public Page $page;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(Page $page): void
    {
        $store = app('current_store');
        abort_unless($page->store_id === $store->id, 404);

        $this->page = $page;
        $this->title = $page->title;
        $this->handle = $page->handle;
        $this->bodyHtml = $page->body_html ?? '';
        $this->status = $page->status->value;
    }

    public function updatedTitle(): void
    {
        $this->handle = Str::slug($this->title);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string', 'max:65000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $wasPublished = $this->page->status === PageStatus::Published;
        $isNowPublished = $validated['status'] === 'published';

        $this->page->update([
            'title' => $validated['title'],
            'handle' => $validated['handle'],
            'body_html' => $validated['bodyHtml'],
            'status' => $validated['status'],
            'published_at' => ($isNowPublished && ! $wasPublished) ? now() : $this->page->published_at,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Page updated.');
    }

    public function render(): View
    {
        return view('livewire.admin.pages.edit', [
            'statuses' => PageStatus::cases(),
        ]);
    }
}

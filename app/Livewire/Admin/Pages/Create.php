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
#[Title('Create Page')]
class Create extends Component
{
    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function updatedTitle(): void
    {
        if ($this->handle === '' || $this->handle === Str::slug($this->title)) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string', 'max:65000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $store = app('current_store');

        $page = Page::query()->create([
            'store_id' => $store->id,
            'title' => $validated['title'],
            'handle' => $validated['handle'],
            'body_html' => $validated['bodyHtml'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Page created.');

        $this->redirect(route('admin.pages.edit', $page), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.create', [
            'statuses' => PageStatus::cases(),
        ]);
    }
}

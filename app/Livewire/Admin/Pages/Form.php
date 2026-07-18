<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Page')]
class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public function mount(?Page $page = null): void
    {
        $this->page = $page?->exists ? $page : null;

        if ($this->page) {
            Gate::authorize('update', $page);
            $this->fill(['title' => $page->title, 'handle' => $page->handle, 'bodyHtml' => $page->body_html ?? '', 'status' => $page->status->value, 'publishedAt' => $page->published_at?->format('Y-m-d\TH:i')]);
        } else {
            Gate::authorize('create', Page::class);
        }
    }

    public function save(): void
    {
        $validated = $this->validate(['title' => ['required', 'string', 'max:255'], 'handle' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'handle')->where('store_id', app('current_store')->id)->ignore($this->page?->id)], 'bodyHtml' => ['nullable', 'string', 'max:65535'], 'status' => ['required', Rule::in(['draft', 'published', 'archived'])], 'publishedAt' => ['nullable', 'date']]);
        $data = ['store_id' => app('current_store')->id, 'title' => $validated['title'], 'handle' => $validated['handle'] ?: Str::slug($validated['title']), 'body_html' => $validated['bodyHtml'], 'status' => $validated['status'], 'published_at' => $validated['status'] === 'published' ? ($validated['publishedAt'] ?: now()) : null];
        $this->page ? $this->page->update($data) : $this->page = Page::query()->create($data);
        session()->flash('toast', 'Page saved.');
        $this->redirectRoute('admin.pages.edit', ['page' => $this->page], navigate: true);
    }

    public function deletePage(): void
    {
        abort_unless($this->page, 404);
        Gate::authorize('delete', $this->page);
        $this->page->delete();
        $this->redirectRoute('admin.pages.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form');
    }
}

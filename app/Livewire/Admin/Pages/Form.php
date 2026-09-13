<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    use ResolvesAdminStore;

    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public bool $showDeleteModal = false;

    public function mount(?Page $page = null): void
    {
        if ($page !== null && $page->exists) {
            abort_if($page->store_id !== $this->store()->getKey(), 404);

            Gate::authorize('view', $page);

            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = (string) $page->body_html;
            $this->status = $page->status->value;
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i');
        } else {
            Gate::authorize('create', Page::class);

            $this->page = null;
        }
    }

    public function updatedTitle(): void
    {
        if ($this->page === null && $this->handle === '') {
            $this->handle = Str::slug($this->title);
        }
    }

    public function save(): mixed
    {
        if ($this->page !== null) {
            Gate::authorize('update', $this->page);
        } else {
            Gate::authorize('create', Page::class);
        }

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'handle' => 'nullable|string|max:255',
            'bodyHtml' => 'nullable|string|max:65535',
            'status' => 'required|in:draft,published,archived',
            'publishedAt' => 'nullable|date',
        ]);

        $handle = $validated['handle'] !== '' && $validated['handle'] !== null
            ? $validated['handle']
            : Str::slug($validated['title']);

        $exists = Page::query()
            ->where('store_id', $this->store()->getKey())
            ->where('handle', $handle)
            ->when($this->page !== null, fn ($query) => $query->whereKeyNot($this->page->getKey()))
            ->exists();

        if ($exists) {
            $this->addError('handle', 'The handle has already been taken.');

            return null;
        }

        $status = PageStatus::from($validated['status']);

        $data = [
            'title' => $validated['title'],
            'handle' => $handle,
            'body_html' => $validated['bodyHtml'] !== '' ? $validated['bodyHtml'] : null,
            'status' => $status,
            'published_at' => $validated['publishedAt'] !== '' && $validated['publishedAt'] !== null
                ? $validated['publishedAt']
                : ($status === PageStatus::Published ? now() : null),
        ];

        if ($this->page !== null) {
            $this->page->update($data);
            $this->toast('success', 'Page saved.');

            return null;
        }

        Page::query()->create(['store_id' => $this->store()->getKey(), ...$data]);

        $this->flashToast('success', 'Page saved.');

        return redirect()->route('admin.pages.index');
    }

    public function deletePage(): mixed
    {
        abort_if($this->page === null, 404);

        Gate::authorize('delete', $this->page);

        $this->page->delete();

        $this->flashToast('success', 'Page deleted.');

        return redirect()->route('admin.pages.index');
    }

    public function render()
    {
        return view('livewire.admin.pages.form', [
            'isEditing' => $this->page !== null,
        ])->layout('livewire.admin.layout.app', ['title' => $this->page !== null ? $this->page->title : 'Add page']);
    }
}

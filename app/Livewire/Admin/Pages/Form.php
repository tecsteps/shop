<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Page;
use App\Support\HandleGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared create/edit content page form (title, handle, body, status, publish
 * date).
 */
#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    use BindsCurrentStore;

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
            $this->authorize('update', $page);
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = (string) $page->body_html;
            $this->status = $page->status->value;
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i');
        } else {
            $this->authorize('create', Page::class);
        }
    }

    public function getIsEditingProperty(): bool
    {
        return $this->page !== null && $this->page->exists;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    public function save(HandleGenerator $handles): mixed
    {
        $this->validate();

        $store = app('current_store');
        $handle = $this->handle !== ''
            ? $handles->generate($this->handle, 'pages', $store->id, $this->page?->id)
            : $handles->generate($this->title, 'pages', $store->id, $this->page?->id);

        $attributes = [
            'title' => $this->title,
            'handle' => $handle,
            'body_html' => $this->bodyHtml !== '' ? $this->bodyHtml : null,
            'status' => $this->status,
            'published_at' => $this->publishedAt !== '' ? $this->publishedAt : null,
        ];

        if ($this->isEditing) {
            $this->page->update($attributes);
        } else {
            $this->page = Page::create($attributes);
        }

        $this->dispatch('toast', type: 'success', message: __('Page saved'));

        if (! $this->isEditing) {
            return $this->redirectRoute('admin.pages.edit', $this->page, navigate: true);
        }

        return null;
    }

    public function deletePage(): mixed
    {
        $this->authorize('delete', $this->page);
        $this->page->delete();

        $this->dispatch('toast', type: 'success', message: __('Page deleted'));

        return $this->redirectRoute('admin.pages.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.pages.form');
    }
}

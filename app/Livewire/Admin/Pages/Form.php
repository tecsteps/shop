<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Page;
use App\Support\HandleGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public bool $confirmingDelete = false;

    public function mount(?Page $page = null): void
    {
        if ($page) {
            $this->authorize('update', $page);

            $this->page = $page;

            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = (string) $page->body_html;
            $this->status = $page->status;
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i');
        } else {
            $this->authorize('create', Page::class);
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->page !== null;
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'handle')
                ->where('store_id', app('current_store')->id)
                ->ignore($this->page?->id)],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'publishedAt' => ['nullable', 'date'],
        ]);

        $store = app('current_store');

        $data = [
            'title' => $this->title,
            'body_html' => $this->bodyHtml !== '' ? $this->bodyHtml : null,
            'status' => $this->status,
            'published_at' => $this->status === 'published'
                ? ($this->publishedAt ? Carbon::parse($this->publishedAt) : now())
                : ($this->publishedAt ? Carbon::parse($this->publishedAt) : null),
        ];

        if ($this->handle !== '') {
            $data['handle'] = $this->handle;
        }

        $page = $this->page ?? new Page(['store_id' => $store->id]);
        $page->fill($data);

        if (empty($page->handle)) {
            $page->handle = app(HandleGenerator::class)->generate($this->title, 'pages', $store->id, $page->id);
        }

        $page->save();

        $this->toast('Page saved');

        if ($this->page === null) {
            $this->redirect(route('admin.pages.edit', $page), navigate: true);
        }
    }

    public function deletePage(): void
    {
        if (! $this->page) {
            return;
        }

        $this->authorize('delete', $this->page);
        $this->confirmingDelete = false;

        $this->page->delete();

        $this->toast('Page deleted');
        $this->redirect(route('admin.pages.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.pages.form');
    }
}

<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public bool $handleManuallyEdited = false;

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public function mount(?Page $page = null): void
    {
        if ($page !== null && $page->exists) {
            $this->authorize('update', $page);

            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = (string) ($page->body_html ?? '');
            $this->status = $page->status->value;
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i');
        } else {
            $this->authorize('create', Page::class);
        }
    }

    /**
     * Auto-generate the URL handle from the title while the user has not
     * edited it manually (spec 03 §13.2).
     */
    public function updatedTitle(string $value): void
    {
        if (! $this->isEditing() && ! $this->handleManuallyEdited) {
            $this->handle = Str::slug($value);
        }
    }

    public function updatedHandle(): void
    {
        $this->handleManuallyEdited = true;
    }

    /**
     * Validate and save the page (spec 03 §13.2). Body HTML is sanitized by
     * the model; publishing sets published_at when not already set.
     */
    public function save(): void
    {
        if ($this->publishedAt === '') {
            $this->publishedAt = null;
        }

        $validated = $this->validate($this->rules());

        /** @var Store $store */
        $store = app('current_store');

        $status = PageStatus::from($validated['status']);
        $publishedAt = $this->resolvePublishedAt($status, $validated['publishedAt'] ?? null);

        $data = [
            'title' => $validated['title'],
            'handle' => $validated['handle'],
            'body_html' => $validated['bodyHtml'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
        ];

        if ($this->isEditing()) {
            $this->authorize('update', $this->page);

            $this->page->update($data);

            $this->dispatch('toast', type: 'success', message: 'Page saved');
        } else {
            $this->authorize('create', Page::class);

            $page = Page::create(array_merge($data, ['store_id' => $store->id]));

            session()->flash('toast', ['type' => 'success', 'message' => 'Page saved']);

            $this->redirect(route('admin.pages.edit', $page));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form')
            ->layout('admin.layouts.app')
            ->title($this->isEditing() ? $this->page->title : 'Create page');
    }

    /**
     * Whether the form is editing an existing page.
     */
    public function isEditing(): bool
    {
        return $this->page !== null && $this->page->exists;
    }

    /**
     * Validation rules (spec 03 §13.2).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        /** @var Store $store */
        $store = app('current_store');

        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'required', 'string', 'max:255',
                Rule::unique('pages', 'handle')
                    ->where('store_id', $store->id)
                    ->ignore($this->page?->id),
            ],
            'bodyHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'publishedAt' => ['nullable', 'date'],
        ];
    }

    /**
     * Publishing auto-sets published_at when none is set; unpublishing or
     * archiving keeps the stored timestamp for history.
     */
    private function resolvePublishedAt(PageStatus $status, ?string $input): ?string
    {
        if ($status === PageStatus::Published) {
            return $input ?? $this->page?->published_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
        }

        return $input;
    }
}

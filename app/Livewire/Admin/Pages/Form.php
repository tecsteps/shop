<?php

namespace App\Livewire\Admin\Pages;

use App\Actions\SanitizeHtml;
use App\Enums\PageStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Page;
use App\Support\HandleGenerator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    use UsesAdminStore;

    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        $this->page = $page?->exists ? $page : null;

        if ($this->page === null) {
            return;
        }

        $this->title = $this->page->title;
        $this->handle = $this->page->handle;
        $this->bodyHtml = $this->page->body_html ?? '';
        $this->status = $this->page->status->value;
    }

    public function save(HandleGenerator $handles, SanitizeHtml $sanitizeHtml): mixed
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_map(fn (PageStatus $status): string => $status->value, PageStatus::cases()))],
        ]);

        $payload = [
            'store_id' => $this->currentStore()->id,
            'title' => $validated['title'],
            'handle' => $validated['handle'] ?: $handles->generate($validated['title'], (new Page)->getTable(), $this->currentStore()->id, $this->page?->id),
            'body_html' => $sanitizeHtml($validated['bodyHtml']),
            'status' => $validated['status'],
            'published_at' => $validated['status'] === PageStatus::Published->value ? now() : null,
        ];

        $this->page = $this->page === null
            ? Page::query()->create($payload)
            : tap($this->page)->update($payload);

        $this->notify('Page saved.');

        return $this->redirect(route('admin.pages.edit', $this->page), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form', [
            'statuses' => PageStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => $this->page ? 'Edit page' : 'Create page',
        ]);
    }
}

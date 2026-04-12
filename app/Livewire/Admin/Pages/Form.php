<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Page $page = null;

    public string $mode = 'create';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public string $handle = '';

    #[Validate('nullable|string')]
    public string $bodyHtml = '';

    #[Validate('required|string|in:draft,published,archived')]
    public string $status = 'draft';

    #[Validate('nullable|date')]
    public ?string $publishedAt = null;

    public function mount(?Page $page = null): void
    {
        if ($page !== null && $page->exists) {
            $this->page = $page;
            $this->mode = 'edit';
            $this->title = (string) $page->title;
            $this->handle = (string) $page->handle;
            $this->bodyHtml = (string) ($page->body_html ?? '');
            $this->status = $page->status->value;
            $this->publishedAt = $page->published_at?->format('Y-m-d');
        }
    }

    public function save(): mixed
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $publishedAt = null;
        if ($this->status === PageStatus::Published->value) {
            $publishedAt = $this->publishedAt !== null && $this->publishedAt !== ''
                ? $this->publishedAt
                : now();
        }

        if ($this->mode === 'create') {
            $handle = $this->handle !== ''
                ? HandleGenerator::generate($this->handle, 'pages', $store->id)
                : HandleGenerator::generate($this->title, 'pages', $store->id);

            Page::create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handle,
                'body_html' => $this->bodyHtml !== '' ? $this->bodyHtml : null,
                'status' => $this->status,
                'published_at' => $publishedAt,
            ]);
        } else {
            $handle = $this->handle !== '' && $this->handle !== $this->page->handle
                ? HandleGenerator::generate($this->handle, 'pages', $store->id, $this->page->id)
                : $this->page->handle;

            $this->page->update([
                'title' => $this->title,
                'handle' => $handle,
                'body_html' => $this->bodyHtml !== '' ? $this->bodyHtml : null,
                'status' => $this->status,
                'published_at' => $publishedAt,
            ]);
        }

        session()->flash('status', 'Page saved.');

        return redirect()->route('admin.pages.index');
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form');
    }
}

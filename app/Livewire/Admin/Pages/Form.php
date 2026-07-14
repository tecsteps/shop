<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\AdminComponent;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class Form extends AdminComponent
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public ?string $publishedAt = null;

    public function mount(?Page $page = null): void
    {
        if ($page?->exists) {
            abort_unless((int) $page->store_id === (int) $this->currentStore()->id, 404);
            $this->authorizeAction('view', $page);
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = (string) $page->body_html;
            $this->status = (string) $this->enumValue($page->status);
            $this->publishedAt = $page->published_at?->format('Y-m-d\TH:i');

            return;
        }

        $this->authorizeAction('create', Page::class);
    }

    public function updatedTitle(string $title): void
    {
        $this->page
            ? $this->authorizeAction('update', $this->page)
            : $this->authorizeAction('create', Page::class);
        if (! $this->page) {
            $this->handle = Str::slug($title);
        }
    }

    public function save(): void
    {
        $this->page
            ? $this->authorizeAction('update', $this->page)
            : $this->authorizeAction('create', Page::class);
        $this->handle = Str::slug($this->handle ?: $this->title);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('pages', 'handle')->where('store_id', $this->currentStore()->id)->ignore($this->page?->id)],
            'bodyHtml' => ['nullable', 'string', 'max:1000000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'publishedAt' => ['nullable', 'date'],
        ]);
        $body = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $data['bodyHtml'] ?? '') ?? '';
        $publishedAt = $data['status'] === 'published' ? ($data['publishedAt'] ?: now()) : null;

        if ($this->page) {
            $this->page->update([
                'title' => trim($data['title']), 'handle' => $data['handle'], 'body_html' => $body,
                'status' => $data['status'], 'published_at' => $publishedAt,
            ]);
        } else {
            $this->page = Page::withoutGlobalScopes()->create([
                'store_id' => $this->currentStore()->id, 'title' => trim($data['title']), 'handle' => $data['handle'],
                'body_html' => $body, 'status' => $data['status'], 'published_at' => $publishedAt,
            ]);
        }
        $this->publishedAt = $this->page->published_at?->format('Y-m-d\TH:i');
        $this->toast('Page saved');
    }

    public function deletePage(): mixed
    {
        abort_unless($this->page, 404);
        $this->authorizeAction('delete', $this->page);
        $this->page->delete();
        $this->toast('Page deleted');

        return $this->redirect('/admin/pages', navigate: true);
    }

    public function render(): View
    {
        $label = $this->page ? $this->title : 'Add page';

        return $this->admin(view('admin.pages.form'), $label, [
            ['label' => 'Pages', 'url' => url('/admin/pages')], ['label' => $label],
        ]);
    }
}

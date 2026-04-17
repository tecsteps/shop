<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Support\HandleGenerator;
use Livewire\Component;

class Form extends Component
{
    public ?int $pageId = null;

    public string $title = '';

    public string $body_html = '';

    public string $status = 'draft';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
        ];
    }

    public function mount(?int $pageId = null): void
    {
        if ($pageId) {
            $page = Page::findOrFail($pageId);
            $this->pageId = $page->id;
            $this->title = $page->title;
            $this->body_html = $page->body_html ?? '';
            $this->status = $page->status->value;
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $store = app('current_store');

        if ($this->pageId) {
            $page = Page::findOrFail($this->pageId);
            $page->update([
                'title' => $this->title,
                'body_html' => $this->body_html ?: null,
                'status' => $this->status,
                'published_at' => $this->status === 'published' ? ($page->published_at ?? now()) : null,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Page updated.');
        } else {
            $handle = app(HandleGenerator::class)->generate($this->title, 'pages', $store->id);
            $page = Page::create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handle,
                'body_html' => $this->body_html ?: null,
                'status' => $this->status,
                'published_at' => $this->status === 'published' ? now() : null,
            ]);
            session()->flash('toast', ['type' => 'success', 'message' => 'Page created.']);

            return redirect()->route('admin.pages.edit', $page);
        }

        return null;
    }

    public function render(): mixed
    {
        $isEdit = (bool) $this->pageId;

        return view('livewire.admin.pages-admin.form', [
            'isEdit' => $isEdit,
        ])->layout('layouts.admin.app', [
            'title' => $isEdit ? "Edit {$this->title}" : 'New Page',
        ]);
    }
}

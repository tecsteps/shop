<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Support\HandleGenerator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $body_html = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->page = $page;
            $this->title = (string) $page->title;
            $this->handle = (string) $page->handle;
            $this->body_html = (string) $page->body_html;
            $this->status = $page->status?->value ?? 'draft';
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'handle' => 'nullable|string|max:255',
            'body_html' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
        ]);

        $store = app('current_store');
        $handle = $data['handle'] !== '' ? $data['handle'] : HandleGenerator::generate($data['title'], 'pages', $store->id, $this->page?->id);

        $payload = [
            'store_id' => $store->id,
            'title' => $data['title'],
            'handle' => $handle,
            'body_html' => $data['body_html'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ];

        if ($this->page && $this->page->exists) {
            $this->page->update($payload);
            $page = $this->page;
        } else {
            $page = Page::create($payload);
            $this->page = $page;
        }

        session()->flash('success', 'Page saved.');

        $this->redirect(route('admin.pages.edit', $page), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.pages.form', [
            'statuses' => PageStatus::cases(),
        ]);
    }
}

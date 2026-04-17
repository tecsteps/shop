<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Page $page = null;

    public string $title = '';

    public string $body_html = '';

    public string $status = 'draft';

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->authorize('update', Page::class);
            $this->page = $page;
            $this->title = $page->title;
            $this->body_html = $page->body_html ?? '';
            $this->status = $page->status->value;
        } else {
            $this->authorize('create', Page::class);
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $store = app('current_store');
        $handleGenerator = app(HandleGenerator::class);

        if ($this->page) {
            $this->page->update([
                'title' => $this->title,
                'handle' => $handleGenerator->generate($this->title, 'pages', $store->id, $this->page->id),
                'body_html' => $this->body_html,
                'status' => $this->status,
                'published_at' => $this->status === 'published' ? now()->toIso8601String() : $this->page->published_at,
            ]);

            session()->flash('toast', ['type' => 'success', 'message' => __('Page updated.')]);
        } else {
            Page::query()->create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handleGenerator->generate($this->title, 'pages', $store->id),
                'body_html' => $this->body_html,
                'status' => $this->status,
                'published_at' => $this->status === 'published' ? now()->toIso8601String() : null,
            ]);

            session()->flash('toast', ['type' => 'success', 'message' => __('Page created.')]);
        }

        $this->redirect(route('admin.pages.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form');
    }
}

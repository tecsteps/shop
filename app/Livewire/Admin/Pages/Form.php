<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use App\Support\HandleGenerator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Page $page = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'published';

    public function mount(?Page $page = null): void
    {
        if ($page?->exists) {
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = (string) $page->body_html;
            $this->status = $page->status->value;
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $storeId = app('current_store')->id;
        $data = [
            'store_id' => $storeId,
            'title' => $this->title,
            'handle' => $this->handle ?: HandleGenerator::generate($this->title, 'pages', $storeId, $this->page?->id),
            'body_html' => $this->bodyHtml ?: null,
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        if ($this->page) {
            $this->page->update($data);
        } else {
            $this->page = Page::create($data);
        }

        session()->flash('success', 'Page saved.');

        return $this->redirect(route('admin.pages.edit', $this->page), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.pages.form')->title($this->page ? 'Edit page' : 'New page');
    }
}

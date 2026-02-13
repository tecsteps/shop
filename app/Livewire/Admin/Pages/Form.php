<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public string $mode = 'create';

    public ?Page $page = null;

    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', 'string'],
        ];
    }

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->mode = 'edit';
            $this->page = $page;
            $this->title = $page->title;
            $this->handle = $page->handle;
            $this->bodyHtml = $page->body_html ?? '';
            $this->status = $page->status->value;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'handle' => $this->handle !== '' ? $this->handle : Str::slug($this->title),
            'body_html' => $this->bodyHtml,
            'status' => $this->status,
        ];

        if ($this->status === 'published' && (! $this->page || ! $this->page->published_at)) {
            $data['published_at'] = now();
        }

        if ($this->mode === 'edit' && $this->page) {
            $this->page->update($data);
            $page = $this->page;
        } else {
            $page = Page::create($data);
        }

        $this->redirect(route('admin.pages.edit', $page), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form');
    }
}

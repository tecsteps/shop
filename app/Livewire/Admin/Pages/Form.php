<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\HasStoreForm;
use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Page'])]
class Form extends Component
{
    use HasStoreForm;

    public ?Page $page = null;

    public string $title = '';

    public string $content = '';

    public string $status = 'draft';

    protected function modelProperty(): string
    {
        return 'page';
    }

    protected function modelClass(): string
    {
        return Page::class;
    }

    public function mount(?Page $page = null): void
    {
        if ($page && $page->exists) {
            $this->authorize('update', $page);
            $this->page = $page;
            $this->title = $page->title;
            $this->content = $page->content ?? '';
            $this->status = $page->status->value;
        }
    }

    public function save(): void
    {
        $store = $this->authorizeAndValidate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => Str::slug($this->title),
            'content' => sanitize_html($this->content),
            'status' => $this->status,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        $this->persistModel($data, 'admin.pages.index');
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.form');
    }
}

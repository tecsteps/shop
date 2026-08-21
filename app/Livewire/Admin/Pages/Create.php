<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    public string $title = '';

    public string $handle = '';

    public string $bodyHtml = '';

    public string $status = 'draft';

    public function save(): void
    {
        $this->authorize('create', Page::class);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'bodyHtml' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
        ]);
        $page = Page::query()->create([
            'title' => $data['title'],
            'handle' => $data['handle'] !== '' ? $data['handle'] : str($data['title'])->slug()->toString(),
            'body_html' => $data['bodyHtml'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);
        $this->redirectRoute('admin.pages.edit', ['page' => $page], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.form', ['page' => null])->layout('layouts.admin');
    }
}

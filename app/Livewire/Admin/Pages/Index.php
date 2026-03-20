<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deletePage(int $id): void
    {
        Page::withoutGlobalScopes()->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Page deleted.');
    }

    #[Computed]
    public function pages(): mixed
    {
        $query = Page::query();

        if ($this->search !== '') {
            $query->where('title', 'like', "%{$this->search}%");
        }

        return $query->latest('updated_at')->paginate(15);
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Pages']]]);
    }
}

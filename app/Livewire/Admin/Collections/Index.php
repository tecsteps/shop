<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        $collections = Collection::query()->latest()->paginate(15);

        return view('livewire.admin.collections.index', [
            'collections' => $collections,
        ]);
    }
}

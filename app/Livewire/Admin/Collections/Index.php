<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function render()
    {
        $collections = Collection::query()->withCount('products')->orderByDesc('updated_at')->get();

        return view('livewire.admin.collections.index', compact('collections'))->title('Collections');
    }
}

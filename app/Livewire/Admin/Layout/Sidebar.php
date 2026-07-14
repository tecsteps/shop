<?php

namespace App\Livewire\Admin\Layout;

use App\Enums\StoreUserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = true;

    public string $currentRoute = '';

    public function mount(): void
    {
        $this->currentRoute = (string) request()->route()?->getName();
    }

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function render(): View
    {
        $store = app('current_store');
        $role = Auth::user()?->roleForStore($store) ?? StoreUserRole::Support;

        return view('admin.layout.sidebar', ['role' => $role->value]);
    }
}

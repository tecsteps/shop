<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

/**
 * The persistent admin sidebar: brand, grouped navigation, and active-item
 * highlighting. Rendered once in the admin layout shell. Collapse/overlay
 * behaviour on mobile is handled by Flux's `collapsible="mobile"` sidebar.
 */
class Sidebar extends Component
{
    public function render()
    {
        return view('livewire.admin.layout.sidebar');
    }
}

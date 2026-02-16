<?php

namespace App\Livewire\Admin\Concerns;

use Livewire\Attributes\Url;

/**
 * Shared search and filter reset for admin index components.
 */
trait HasAdminSearch
{
    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}

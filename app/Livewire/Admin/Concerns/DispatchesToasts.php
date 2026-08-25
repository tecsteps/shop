<?php

namespace App\Livewire\Admin\Concerns;

trait DispatchesToasts
{
    /**
     * Dispatch a toast notification rendered by the admin layout.
     *
     * @param  'success'|'error'|'info'  $type
     */
    public function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }
}

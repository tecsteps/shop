<?php

namespace App\Livewire\Admin\Concerns;

trait SendsToasts
{
    /**
     * Dispatch a toast to the global layout listener (spec 03 section 1.5).
     */
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    /**
     * Flash a toast into the session for display after a full redirect.
     */
    protected function flashToast(string $message, string $type = 'success'): void
    {
        session()->flash('toast', ['type' => $type, 'message' => $message]);
    }
}

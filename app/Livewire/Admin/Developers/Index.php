<?php

namespace App\Livewire\Admin\Developers;

use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public function generateToken(): void
    {
        if (! $this->newTokenName) {
            $this->dispatch('toast', type: 'error', message: 'Please enter a token name.');

            return;
        }

        $this->generatedToken = 'sk_live_'.Str::random(40);
        $this->newTokenName = '';
        $this->modal('generate-token')->close();
        $this->dispatch('toast', type: 'success', message: 'Token generated. Copy it now.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.developers.index');
    }
}

<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\App;
use App\Models\AppInstallation;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    use UsesAdminStore;

    public string $installation;

    public function mount(string $installation): void
    {
        $this->installation = $installation;
    }

    public function render(): View
    {
        $app = App::query()->where('handle', $this->installation)->firstOrFail();
        $installation = AppInstallation::withoutGlobalScopes()
            ->with('webhookSubscriptions')
            ->where('store_id', $this->currentStore()->id)
            ->where('app_id', $app->id)
            ->first();

        return view('livewire.admin.apps.show', [
            'app' => $app,
            'appInstallation' => $installation,
        ])->layout('livewire.admin.layout.app', [
            'title' => 'App detail',
        ]);
    }
}

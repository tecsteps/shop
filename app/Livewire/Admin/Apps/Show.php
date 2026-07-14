<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\AdminComponent;
use App\Models\AppInstallation;
use App\Models\WebhookDelivery;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Show extends AdminComponent
{
    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        $this->authorizeApps();
        abort_unless((int) $installation->store_id === (int) $this->currentStore()->id, 404);
        $this->installation = $installation->load('app');
    }

    public function suspend(): void
    {
        $this->authorizeApps();
        $this->scopedInstallation()->update(['status' => 'suspended']);
        $this->installation->refresh()->load('app');
        $this->toast('App suspended');
    }

    public function resume(): void
    {
        $this->authorizeApps();
        $this->scopedInstallation()->update(['status' => 'active']);
        $this->installation->refresh()->load('app');
        $this->toast('App resumed');
    }

    public function uninstall(): mixed
    {
        $this->authorizeApps();
        $this->scopedInstallation()->update(['status' => 'uninstalled']);
        $this->toast('App uninstalled');

        return $this->redirect('/admin/apps', navigate: true);
    }

    #[Computed]
    public function webhooks(): mixed
    {
        return $this->scopedInstallation()->webhookSubscriptions()->with('deliveries')->orderBy('event_type')->get();
    }

    #[Computed]
    public function usage(): array
    {
        $deliveries = WebhookDelivery::query()->whereHas('subscription', fn ($query) => $query->where('app_installation_id', $this->installation->id))->get();

        return ['calls' => $deliveries->count(), 'last_call_at' => $deliveries->max('last_attempt_at')];
    }

    public function render(): View
    {
        return $this->admin(view('admin.apps.show'), $this->installation->app->name, [
            ['label' => 'Apps', 'url' => url('/admin/apps')], ['label' => $this->installation->app->name],
        ]);
    }

    private function authorizeApps(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }

    private function scopedInstallation(): AppInstallation
    {
        return AppInstallation::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->findOrFail($this->installation->id);
    }
}

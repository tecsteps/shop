<?php

namespace App\Livewire\Admin\Apps;

use App\Enums\AppInstallationStatus;
use App\Enums\AppStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\App as AppModel;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * App directory (spec 03 section 15): installed app cards linking to the
 * detail page plus the directory of available apps with an install action.
 */
#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts;

    public function mount(): void
    {
        $this->authorize('manageApps', $this->store());
    }

    public function installApp(int $appId): void
    {
        $this->authorize('manageApps', $this->store());

        $app = AppModel::query()
            ->where('status', AppStatus::Active)
            ->findOrFail($appId);

        $installation = AppInstallation::query()
            ->where('store_id', $this->store()->getKey())
            ->where('app_id', $app->getKey())
            ->first();

        if ($installation !== null && $installation->status !== AppInstallationStatus::Uninstalled) {
            $this->toast(__('This app is already installed.'), 'error');

            return;
        }

        if ($installation !== null) {
            $installation->update([
                'status' => AppInstallationStatus::Active,
                'installed_at' => now(),
            ]);
        } else {
            AppInstallation::query()->create([
                'store_id' => $this->store()->getKey(),
                'app_id' => $app->getKey(),
                'scopes_json' => ['read-products', 'read-orders', 'read-customers'],
                'status' => AppInstallationStatus::Active,
                'installed_at' => now(),
            ]);
        }

        unset($this->installedApps, $this->availableApps);

        $this->toast(__(':app installed.', ['app' => $app->name]));
    }

    public function uninstallApp(int $installationId): void
    {
        $this->authorize('manageApps', $this->store());

        $installation = AppInstallation::query()
            ->where('store_id', $this->store()->getKey())
            ->findOrFail($installationId);

        $installation->update(['status' => AppInstallationStatus::Uninstalled]);

        $installation->webhookSubscriptions()->update([
            'status' => WebhookSubscriptionStatus::Disabled,
        ]);

        unset($this->installedApps, $this->availableApps);

        $this->toast(__(':app uninstalled.', ['app' => $installation->app->name]));
    }

    /**
     * @return Collection<int, AppInstallation>
     */
    #[Computed]
    public function installedApps(): Collection
    {
        return AppInstallation::query()
            ->with('app')
            ->where('store_id', $this->store()->getKey())
            ->where('status', '!=', AppInstallationStatus::Uninstalled)
            ->orderBy('installed_at', 'desc')
            ->get();
    }

    /**
     * @return Collection<int, AppModel>
     */
    #[Computed]
    public function availableApps(): Collection
    {
        $installedAppIds = AppInstallation::query()
            ->where('store_id', $this->store()->getKey())
            ->where('status', '!=', AppInstallationStatus::Uninstalled)
            ->pluck('app_id');

        return AppModel::query()
            ->where('status', AppStatus::Active)
            ->whereNotIn('id', $installedAppIds)
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.apps.index')->title(__('Apps'));
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}

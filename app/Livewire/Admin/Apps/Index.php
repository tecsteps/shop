<?php

namespace App\Livewire\Admin\Apps;

use App\Models\App;
use App\Models\AppInstallation;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Apps page: installed apps and the installable catalog (spec 03 §15).
 */
class Index extends Component
{
    /**
     * Static catalog of apps available for installation.
     *
     * @var list<array{name: string, description: string, scopes: list<string>}>
     */
    public const CATALOG = [
        [
            'name' => 'My Integration App',
            'description' => 'Syncs products and orders with your external systems.',
            'scopes' => ['read-products', 'write-products', 'read-orders'],
        ],
        [
            'name' => 'Analytics Plugin',
            'description' => 'Sends storefront and order events to your analytics warehouse.',
            'scopes' => ['read-orders', 'read-analytics'],
        ],
        [
            'name' => 'Review Connector',
            'description' => 'Imports product reviews from your review provider.',
            'scopes' => ['read-products', 'read-customers'],
        ],
    ];

    public function mount(): void
    {
        Gate::authorize('manage-apps');
    }

    /**
     * Install a catalog app: creates the app record on first use and an
     * active installation with the catalog's default scopes.
     */
    public function installApp(string $appName): void
    {
        Gate::authorize('manage-apps');

        $entry = collect(self::CATALOG)->firstWhere('name', $appName);

        abort_if($entry === null, 404);

        $app = App::query()->firstOrCreate(['name' => $entry['name']], ['status' => 'active']);

        $installation = AppInstallation::query()->where('app_id', $app->id)->first();

        if ($installation !== null) {
            $installation->forceFill([
                'status' => 'active',
                'scopes_json' => $entry['scopes'],
                'installed_at' => now(),
            ])->save();
        } else {
            AppInstallation::query()->create([
                'app_id' => $app->id,
                'scopes_json' => $entry['scopes'],
                'status' => 'active',
                'installed_at' => now(),
            ]);
        }

        $this->dispatch('toast', type: 'success', message: "{$entry['name']} installed");
    }

    /**
     * Uninstall an app (keeps the record, marks it uninstalled).
     */
    public function uninstallApp(int $installationId): void
    {
        Gate::authorize('manage-apps');

        $installation = AppInstallation::query()->findOrFail($installationId);
        $installation->forceFill(['status' => 'uninstalled'])->save();

        $this->dispatch('toast', type: 'success', message: 'App uninstalled');
    }

    public function render(): View
    {
        $installations = AppInstallation::query()
            ->with('app')
            ->where('status', 'active')
            ->orderByDesc('installed_at')
            ->get();

        $availableApps = collect(self::CATALOG)
            ->reject(fn (array $entry): bool => $installations->contains(fn (AppInstallation $installation): bool => $installation->app?->name === $entry['name']))
            ->values();

        return view('livewire.admin.apps.index', [
            'installedApps' => $installations,
            'availableApps' => $availableApps,
        ])->layout('admin.layouts.app')->title('Apps');
    }
}

<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class Index extends AdminComponent
{
    #[Url(as: 'tab', except: 'general')]
    public string $activeTab = 'general';

    public string $storeName = '';

    public string $storeHandle = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    public string $newHostname = '';

    public string $newType = 'storefront';

    public function mount(): void
    {
        $this->authorizeSettings();
        abort_unless(in_array($this->activeTab, ['general', 'domains'], true), 404);
        $store = $this->currentStore();
        $this->storeName = $store->name;
        $this->storeHandle = $store->handle;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $data = $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'defaultCurrency' => ['required', Rule::in(['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD'])],
            'defaultLocale' => ['required', Rule::in(['en', 'de', 'fr', 'es', 'it', 'nl'])],
            'timezone' => ['required', 'timezone'],
        ]);

        $this->currentStore()->update([
            'name' => trim($data['storeName']),
            'default_currency' => $data['defaultCurrency'],
            'default_locale' => $data['defaultLocale'],
            'timezone' => $data['timezone'],
        ]);
        $this->toast('Settings saved');
    }

    public function selectTab(string $tab): void
    {
        $this->authorizeSettings();
        abort_unless(in_array($tab, ['general', 'domains'], true), 404);
        $this->activeTab = $tab;
    }

    public function addDomain(): void
    {
        $this->authorizeSettings();
        $this->newHostname = strtolower(trim($this->newHostname));
        $data = $this->validate([
            'newHostname' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)(?!-)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', Rule::unique('store_domains', 'hostname')],
            'newType' => ['required', Rule::in(['storefront', 'admin', 'api'])],
        ]);

        $isPrimary = ! StoreDomain::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->where('type', $data['newType'])->exists();
        StoreDomain::withoutGlobalScopes()->create([
            'store_id' => $this->currentStore()->id,
            'hostname' => $data['newHostname'],
            'type' => $data['newType'],
            'is_primary' => $isPrimary,
            'tls_mode' => 'managed',
        ]);
        $this->reset('newHostname');
        $this->newType = 'storefront';
        $this->dispatch('modal-close', name: 'add-domain');
        unset($this->domains);
        $this->toast('Domain added');
    }

    public function removeDomain(int $domainId): void
    {
        $this->authorizeSettings();
        $domain = $this->domain($domainId);
        abort_if($domain->is_primary, 422, 'Choose another primary domain before deleting this one.');
        $domain->delete();
        unset($this->domains);
        $this->toast('Domain removed');
    }

    public function setPrimary(int $domainId): void
    {
        $this->authorizeSettings();
        $domain = $this->domain($domainId);
        DB::transaction(function () use ($domain): void {
            StoreDomain::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->where('type', $this->enumValue($domain->type))->update(['is_primary' => false]);
            $domain->update(['is_primary' => true]);
        });
        unset($this->domains);
        $this->toast('Primary domain updated');
    }

    #[Computed]
    public function domains(): mixed
    {
        return StoreDomain::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->orderByDesc('is_primary')->orderBy('hostname')->get();
    }

    public function render(): View
    {
        return $this->admin(view('admin.settings.index'), 'Store Settings', [['label' => 'Settings']]);
    }

    private function authorizeSettings(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }

    private function domain(int $id): StoreDomain
    {
        return StoreDomain::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->findOrFail($id);
    }
}

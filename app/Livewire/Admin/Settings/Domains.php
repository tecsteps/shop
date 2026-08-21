<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreDomain;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Domains extends Component
{
    public string $newHostname = '';

    public string $newType = 'storefront';

    public bool $showModal = false;

    public function mount(): void
    {
        $this->authorize('update', app('current_store'));
    }

    public function addDomain(): void
    {
        $this->authorize('update', app('current_store'));
        $data = $this->validate([
            'newHostname' => ['required', 'string', 'max:255', 'regex:/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'],
            'newType' => ['required', 'in:storefront,admin,api'],
        ]);
        $store = app('current_store');
        $isPrimary = ! StoreDomain::query()->where('store_id', $store->getKey())->where('type', $data['newType'])->exists();
        StoreDomain::create([
            'store_id' => $store->getKey(),
            'hostname' => strtolower($data['newHostname']),
            'type' => $data['newType'],
            'is_primary' => $isPrimary,
            'tls_mode' => 'managed',
        ]);
        $this->reset(['newHostname', 'showModal']);
        $this->newType = 'storefront';
        $this->dispatch('toast', message: 'Domain added.');
    }

    public function removeDomain(int $domainId): void
    {
        $this->authorize('update', app('current_store'));
        $domain = StoreDomain::query()->where('store_id', app('current_store')->getKey())->findOrFail($domainId);
        $wasPrimary = $domain->is_primary;
        $type = $domain->type->value;
        $domain->delete();

        if ($wasPrimary) {
            StoreDomain::query()->where('store_id', app('current_store')->getKey())->where('type', $type)->latest('id')->first()?->update(['is_primary' => true]);
        }
        $this->dispatch('toast', message: 'Domain removed.');
    }

    public function setPrimary(int $domainId): void
    {
        $this->authorize('update', app('current_store'));
        $domain = StoreDomain::query()->where('store_id', app('current_store')->getKey())->findOrFail($domainId);
        DB::transaction(function () use ($domain): void {
            StoreDomain::query()->where('store_id', $domain->store_id)->where('type', $domain->type->value)->update(['is_primary' => false]);
            $domain->update(['is_primary' => true]);
        });
        $this->dispatch('toast', message: 'Primary domain updated.');
    }

    public function render(): mixed
    {
        $domains = StoreDomain::query()->where('store_id', app('current_store')->getKey())->orderByDesc('is_primary')->orderBy('hostname')->get();

        return view('livewire.admin.settings.domains', compact('domains'))->layout('layouts.admin');
    }
}

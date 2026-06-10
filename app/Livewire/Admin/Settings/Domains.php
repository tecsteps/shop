<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Models\StoreDomain;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Domains settings tab (spec 03 section 11.2): list, add, remove store
 * domains and manage the primary flag.
 */
class Domains extends Component
{
    use AuthorizesRequests, SendsToasts;

    public string $newHostname = '';

    public string $newType = 'storefront';

    public function mount(): void
    {
        $this->authorize('viewSettings', $this->store());
    }

    public function addDomain(): void
    {
        $this->authorize('updateSettings', $this->store());

        $this->validate([
            'newHostname' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i',
                Rule::unique('store_domains', 'hostname'),
            ],
            'newType' => ['required', 'in:storefront,admin,api'],
        ]);

        $this->store()->domains()->create([
            'hostname' => strtolower(trim($this->newHostname)),
            'type' => $this->newType,
            'is_primary' => ! $this->store()->domains()->where('type', $this->newType)->exists(),
            'tls_mode' => 'managed',
        ]);

        Flux::modal('add-domain')->close();

        $this->reset('newHostname', 'newType');
        $this->toast(__('Domain added.'));
    }

    public function removeDomain(int $domainId): void
    {
        $this->authorize('updateSettings', $this->store());

        $domain = $this->store()->domains()->findOrFail($domainId);

        if ($this->store()->domains()->count() <= 1) {
            $this->toast(__('A store must keep at least one domain.'), 'error');

            return;
        }

        $domain->delete();

        if ($domain->is_primary) {
            $this->store()->domains()
                ->where('type', $domain->type)
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }

        $this->toast(__('Domain removed.'));
    }

    public function setPrimary(int $domainId): void
    {
        $this->authorize('updateSettings', $this->store());

        $domain = $this->store()->domains()->findOrFail($domainId);

        $this->store()->domains()
            ->where('type', $domain->type)
            ->whereKeyNot($domain->getKey())
            ->update(['is_primary' => false]);

        $domain->update(['is_primary' => true]);

        $this->toast(__('Primary domain updated.'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, StoreDomain>
     */
    #[Computed]
    public function domains(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->store()->domains()->orderBy('type')->orderByDesc('is_primary')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.domains');
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}

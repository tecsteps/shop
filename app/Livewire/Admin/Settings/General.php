<?php

namespace App\Livewire\Admin\Settings;

use App\Models\StoreSettings;
use Livewire\Component;

class General extends Component
{
    public string $storeName = '';

    public string $message = '';

    public function mount(): void
    {
        $settings = StoreSettings::first();
        $this->storeName = (string) ($settings?->general_json['store_name'] ?? app('current_store')->name);
    }

    public function save(): void
    {
        StoreSettings::updateOrCreate(['store_id' => app('current_store')->getKey()], ['general_json' => ['store_name' => $this->storeName]]);
        app('current_store')->update(['name' => $this->storeName]);
        $this->message = 'Settings saved';
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.general')->layout('layouts.admin');
    }
}

<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        $collections = collect();

        if (class_exists(\App\Models\Collection::class)) {
            $collections = \App\Models\Collection::query()
                ->where('status', CollectionStatus::Active)
                ->orderBy('title')
                ->get();
        }

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}

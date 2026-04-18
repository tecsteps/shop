<?php

namespace App\Livewire\Storefront\Collections;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    public function render()
    {
        $collections = [];

        if (Schema::hasTable('collections') && app()->bound('current_store')) {
            $collections = DB::table('collections')
                ->where('store_id', app('current_store')->id)
                ->where('status', 'active')
                ->orderBy('title')
                ->get(['id', 'title', 'handle'])
                ->all();
        }

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}

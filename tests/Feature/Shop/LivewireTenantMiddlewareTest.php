<?php

use App\Http\Middleware\ResolveStore;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;

it('replays tenant resolution for Livewire update requests', function () {
    expect(app(PersistentMiddleware::class)->getPersistentMiddleware())
        ->toContain(ResolveStore::class);
});

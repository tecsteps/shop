<?php

use App\Models\Store;

it('allows authenticated assigned admins to reach core admin pages', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    $paths = [
        '/admin',
        '/admin/products',
        '/admin/collections',
        '/admin/pages',
        '/admin/inventory',
        '/admin/orders',
        '/admin/customers',
        '/admin/discounts',
        '/admin/navigation',
        '/admin/themes',
        '/admin/search/settings',
        '/admin/analytics',
        '/admin/apps',
        '/admin/developers',
        '/admin/settings',
        '/admin/settings/shipping',
        '/admin/settings/taxes',
    ];

    $this->actingAs($user);

    foreach ($paths as $path) {
        $this->withSession(['current_store_id' => $store->id])
            ->get($path)
            ->assertOk();
    }
});

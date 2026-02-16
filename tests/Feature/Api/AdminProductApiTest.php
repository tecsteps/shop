<?php

it('returns 401 without token', function () {
    $ctx = createStoreContext();

    $response = $this->getJson('/api/admin/stores/'.$ctx['store']->id.'/products');

    $response->assertStatus(401);
});

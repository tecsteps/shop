<?php

it('returns 401 without token', function () {
    $ctx = createStoreContext();

    $response = $this->getJson('/api/admin/stores/'.$ctx['store']->id.'/orders');

    $response->assertStatus(401);
});

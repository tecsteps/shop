<?php

it('user model does not yet have HasApiTokens trait', function () {
    $ctx = createStoreContext();

    // Sanctum token support requires HasApiTokens trait on User model
    expect(method_exists($ctx['user'], 'createToken'))->toBeFalse();
});

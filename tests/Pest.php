<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

pest()->browser()->timeout(15000);

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something()
{
    // ..
}

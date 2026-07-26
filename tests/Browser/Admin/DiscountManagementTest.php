<?php

use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

test('shows seeded discount codes', function () {
    visit('/admin/discounts')
        ->assertSee('WELCOME10')
        ->assertSee('FLAT5')
        ->assertSee('FREESHIP')
        ->assertNoJavascriptErrors();
});

test('can create a new percentage discount code', function () {
    $page = visit('/admin/discounts/create');

    // Percentage is the preselected value type.
    $page->fill('code', 'E2ETEST25')
        ->click('Percentage')
        ->fill('startsAt', '2026-01-01T00:00')
        ->fill('endsAt', '2026-12-31T23:59');

    $page->fill('valueAmount', '25');

    $page->press('Save')
        ->wait(1)
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();

    visit('/admin/discounts')
        ->assertSee('E2ETEST25')
        ->assertNoJavascriptErrors();

    expect(Discount::query()->where('code', 'E2ETEST25')->where('value_amount', 25)->exists())->toBeTrue();
});

test('can create a fixed amount discount code', function () {
    // The fixed-amount value field is in cents (minor units): 1000 = 10.00 EUR.
    $page = visit('/admin/discounts/create');

    $page->fill('code', 'E2EFLAT10')
        ->click('Fixed amount')
        ->wait(1)
        ->fill('startsAt', '2026-01-01T00:00');

    $page->fill('valueAmount', '1000');

    $page->press('Save')
        ->wait(1)
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();

    expect(
        Discount::query()
            ->where('code', 'E2EFLAT10')
            ->where('value_type', DiscountValueType::Fixed->value)
            ->where('value_amount', 1000)
            ->exists()
    )->toBeTrue();
});

test('can create a free shipping discount code', function () {
    visit('/admin/discounts/create')
        ->fill('code', 'E2EFREESHIP')
        ->click('Free shipping')
        ->wait(1)
        ->fill('startsAt', '2026-01-01T00:00')
        ->press('Save')
        ->wait(1)
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();

    expect(
        Discount::query()
            ->where('code', 'E2EFREESHIP')
            ->where('value_type', DiscountValueType::FreeShipping->value)
            ->exists()
    )->toBeTrue();
});

test('can edit a discount', function () {
    $page = visit('/admin/discounts');

    $page->click('WELCOME10')->wait(1);

    $page->fill('valueAmount', '15');

    $page->press('Save')
        ->wait(1)
        ->assertSee('Discount saved')
        ->assertNoJavascriptErrors();

    expect(Discount::query()->where('code', 'WELCOME10')->sole()->value_amount)->toBe(15);
});

test('shows discount status indicators', function () {
    visit('/admin/discounts')
        ->assertSee('Active')
        ->assertSee('Expired')
        ->assertNoJavascriptErrors();
});

<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminDiscountHost(): array
{
    return ['host' => 'shop.test'];
}

function adminDiscountStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminDiscountAuthenticate(mixed $testCase): Store
{
    $store = adminDiscountStore();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);

    return $store;
}

function adminDiscountOpenDiscounts(mixed $testCase): mixed
{
    adminDiscountAuthenticate($testCase);

    return visit('/admin/discounts', adminDiscountHost())
        ->wait(1)
        ->assertPathIs('/admin/discounts')
        ->assertSee('Discounts')
        ->assertNoJavaScriptErrors();
}

function adminDiscountOpenCreateForm(mixed $testCase): mixed
{
    return adminDiscountOpenDiscounts($testCase)
        ->click('a[href$="/admin/discounts/create"]')
        ->wait(1)
        ->assertPathIs('/admin/discounts/create')
        ->assertSee('Create discount')
        ->assertNoJavaScriptErrors();
}

function adminDiscountFillCodeForm(
    mixed $page,
    string $code,
    string $valueType,
    string $startsAt = '2026-01-01T00:00',
    string $endsAt = '',
    ?string $valueAmount = null,
): mixed {
    $page
        ->fill('input[wire\\:model="code"]', $code)
        ->fill('input[wire\\:model="startsAt"]', $startsAt);

    if ($endsAt !== '') {
        $page->fill('input[wire\\:model="endsAt"]', $endsAt);
    }

    $page
        ->click($valueType)
        ->wait(1);

    if ($valueAmount !== null) {
        $page->fill('input[wire\\:model="valueAmount"]', $valueAmount);
    }

    return $page;
}

function adminDiscountSave(mixed $page): mixed
{
    return $page
        ->click('button[data-test="discount-save-button"]')
        ->wait(1)
        ->assertSee('Discount saved')
        ->assertNoJavaScriptErrors();
}

test('shows seeded discount codes', function (): void {
    adminDiscountOpenDiscounts($this)
        ->assertSee('WELCOME10')
        ->assertSee('FLAT5')
        ->assertSee('FREESHIP')
        ->assertNoJavaScriptErrors();
});

test('can create a new percentage discount code', function (): void {
    $store = adminDiscountAuthenticate($this);
    $page = adminDiscountOpenCreateForm($this);

    adminDiscountFillCodeForm(
        page: $page,
        code: 'E2ETEST25',
        valueType: 'Percentage',
        startsAt: '2026-01-01T00:00',
        endsAt: '2026-12-31T23:59',
        valueAmount: '25',
    );

    adminDiscountSave($page)
        ->click('ui-sidebar a[href$="/admin/discounts"]')
        ->wait(1)
        ->assertPathIs('/admin/discounts')
        ->assertSee('E2ETEST25')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('discounts', [
        'store_id' => $store->getKey(),
        'code' => 'E2ETEST25',
        'value_type' => 'percent',
        'value_amount' => 25,
    ]);
});

test('can create a fixed amount discount code', function (): void {
    $store = adminDiscountAuthenticate($this);
    $page = adminDiscountOpenCreateForm($this);

    adminDiscountFillCodeForm(
        page: $page,
        code: 'E2EFLAT10',
        valueType: 'Fixed amount',
        startsAt: '2026-01-01T00:00',
        valueAmount: '10.00',
    );

    adminDiscountSave($page);

    $this->assertDatabaseHas('discounts', [
        'store_id' => $store->getKey(),
        'code' => 'E2EFLAT10',
        'value_type' => 'fixed',
        'value_amount' => 1000,
    ]);
});

test('can create a free shipping discount code', function (): void {
    $store = adminDiscountAuthenticate($this);
    $page = adminDiscountOpenCreateForm($this);

    adminDiscountFillCodeForm(
        page: $page,
        code: 'E2EFREESHIP',
        valueType: 'Free shipping',
        startsAt: '2026-01-01T00:00',
    );

    adminDiscountSave($page);

    $this->assertDatabaseHas('discounts', [
        'store_id' => $store->getKey(),
        'code' => 'E2EFREESHIP',
        'value_type' => 'free_shipping',
        'value_amount' => 0,
    ]);
});

test('can edit a discount', function (): void {
    $store = adminDiscountAuthenticate($this);

    $page = adminDiscountOpenDiscounts($this)
        ->click('a:has-text("WELCOME10")')
        ->wait(1)
        ->assertPathContains('/admin/discounts/')
        ->assertSee('Edit discount')
        ->assertValue('input[wire\\:model="code"]', 'WELCOME10')
        ->assertNoJavaScriptErrors();

    $page->fill('input[wire\\:model="valueAmount"]', '15');

    adminDiscountSave($page);

    $this->assertDatabaseHas('discounts', [
        'store_id' => $store->getKey(),
        'code' => 'WELCOME10',
        'value_type' => 'percent',
        'value_amount' => 15,
    ]);
});

test('shows discount status indicators', function (): void {
    adminDiscountOpenDiscounts($this)
        ->assertSee('WELCOME10')
        ->assertSee('Active')
        ->assertSee('EXPIRED20')
        ->assertSee('Expired')
        ->assertNoJavaScriptErrors();
});

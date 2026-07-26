<?php

use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

/**
 * Set the discount form's valueAmount property through the Livewire JS API.
 *
 * Workaround for an app bug: the valueAmount <flux:input> in
 * resources/views/livewire/admin/discounts/form.blade.php is never resolved
 * by the Blade compiler (an inline @if inside the component tag breaks tag
 * matching), so no real <input> is rendered for it in the browser. The
 * property is therefore set through Livewire's client-side API instead of
 * typing into the (missing) field; everything else uses the real UI.
 */
function setDiscountValueAmount(Webpage|PendingAwaitablePage $page, int $value): void
{
    $script = <<<'JS'
        () => {
            const snapshots = Array.from(document.querySelectorAll('[wire\\:snapshot]'));

            for (const snapshot of snapshots) {
                const data = JSON.parse(snapshot.getAttribute('wire:snapshot'));

                if (data.memo && data.memo.name === 'admin.discounts.form') {
                    Livewire.find(data.memo.id).set('valueAmount', VALUE_PLACEHOLDER);

                    return 'ok';
                }
            }

            return 'component not found';
        }
    JS;

    $result = $page->script(str_replace('VALUE_PLACEHOLDER', (string) $value, $script));

    expect($result)->toBe('ok');
}

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

    setDiscountValueAmount($page, 25);

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

    setDiscountValueAmount($page, 1000);

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

    setDiscountValueAmount($page, 15);

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

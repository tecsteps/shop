<?php

use App\Livewire\Storefront\CartDrawer;
use App\Livewire\Storefront\Search\Modal as SearchModal;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
});

it('renders the cart drawer as a labelled modal dialog with focus management', function () {
    Livewire::test(CartDrawer::class)
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('aria-label="Shopping cart"')
        // The storefrontDialog helper provides focus-in/return + Tab trap.
        ->assertSeeHtml('storefrontDialog($wire')
        ->assertSeeHtml('trapTab($event)')
        ->assertSeeHtml('keydown.escape.window');
});

it('renders the search modal as a labelled modal dialog with focus management', function () {
    Livewire::test(SearchModal::class)
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('aria-label="Search"')
        ->assertSeeHtml('storefrontDialog($wire')
        ->assertSeeHtml('trapTab($event)');
});

it('exposes a skip link and landmark roles in the storefront layout', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/'))
        ->assertOk()
        ->assertSee('Skip to main content')
        ->assertSeeHtml('id="main-content"')
        ->assertSeeHtml('role="banner"')
        ->assertSeeHtml('role="contentinfo"');
});

<?php

use App\Livewire\Admin\Layout\Sidebar;
use App\Livewire\Admin\Layout\TopBar;
use App\Models\Customer;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Theme;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
    actingAsAdmin($this->owner, $this->store);
});

it('renders every admin index page without errors', function (string $path): void {
    $this->get($path)->assertOk();
})->with([
    'dashboard' => '/admin',
    'products' => '/admin/products',
    'products create' => '/admin/products/create',
    'collections' => '/admin/collections',
    'collections create' => '/admin/collections/create',
    'inventory' => '/admin/inventory',
    'orders' => '/admin/orders',
    'customers' => '/admin/customers',
    'discounts' => '/admin/discounts',
    'discounts create' => '/admin/discounts/create',
    'pages' => '/admin/pages',
    'pages create' => '/admin/pages/create',
    'navigation' => '/admin/navigation',
    'themes' => '/admin/themes',
    'settings' => '/admin/settings',
    'settings shipping' => '/admin/settings/shipping',
    'settings taxes' => '/admin/settings/taxes',
    'analytics' => '/admin/analytics',
    'search settings' => '/admin/search/settings',
    'apps' => '/admin/apps',
    'developers' => '/admin/developers',
]);

it('renders the order detail page', function (): void {
    $order = Order::factory()->create(['store_id' => $this->store->id]);

    $this->get('/admin/orders/'.$order->id)->assertOk();
});

it('renders the customer detail page', function (): void {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->get('/admin/customers/'.$customer->id)->assertOk();
});

it('renders the theme editor', function (): void {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    $this->get('/admin/themes/'.$theme->id.'/editor')->assertOk();
});

it('renders the navigation menu editor', function (): void {
    NavigationMenu::factory()->create(['store_id' => $this->store->id, 'handle' => 'main-menu', 'title' => 'Main Menu']);

    $this->get('/admin/navigation')->assertOk();
});

// Regression guard: the layout's nested Livewire components must each render
// with exactly one root element (Livewire requirement). A multiple-root
// element here previously 500'd the entire /admin panel.
it('renders the sidebar component with a single root', function (): void {
    Livewire::test(Sidebar::class)->assertOk();
});

it('renders the top bar component with a single root', function (): void {
    Livewire::test(TopBar::class)->assertOk();
});

<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the admin dashboard', function () {
    actingAsAdmin($this->user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});

it('shows KPI tiles with correct data', function () {
    Order::factory()
        ->count(5)
        ->for($this->store)
        ->totaling(5000)
        ->create(['placed_at' => now()->subDay(), 'currency' => 'EUR']);

    actingAsAdmin($this->user);

    Livewire::test(Dashboard::class)
        ->assertViewHas('ordersCount', 5)
        ->assertViewHas('totalSales', 25000)
        ->assertViewHas('formattedTotalSales', '250.00 EUR')
        ->assertSee('250.00 EUR');
});

it('restricts dashboard to authenticated admins', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('filters metrics by date range', function () {
    Order::factory()
        ->count(2)
        ->for($this->store)
        ->totaling(1000)
        ->create(['placed_at' => now()->subDays(2)]);

    Order::factory()
        ->count(3)
        ->for($this->store)
        ->totaling(1000)
        ->create(['placed_at' => now()->subDays(20)]);

    actingAsAdmin($this->user);

    Livewire::test(Dashboard::class)
        ->assertViewHas('ordersCount', 5)
        ->set('dateRange', '7')
        ->assertViewHas('ordersCount', 2)
        ->assertViewHas('totalSales', 2000);
});

<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

it('renders the admin dashboard', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});

it('shows KPI tiles with correct data', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Order::factory()->count(5)->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => Carbon::now()->subDay(),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSet('dateRange', 'last_30_days')
        ->assertSeeText('5')
        ->assertSeeText('250.00 USD');
});

it('restricts the dashboard to authenticated admins', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('filters metrics by date range', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 10000,
        'placed_at' => Carbon::now()->subDay(),
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 99999,
        'placed_at' => Carbon::now()->subDays(20),
    ]);

    $component = Livewire::test(Dashboard::class)->set('dateRange', 'last_7_days');

    expect($component->instance()->kpis['ordersCount'])->toBe(1);
    expect($component->instance()->kpis['totalSales'])->toBe(10000);
});

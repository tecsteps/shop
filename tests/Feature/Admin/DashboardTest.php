<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
});

test('renders the admin dashboard', function () {
    $user = $this->createUserWithRole($this->store, 'admin');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});

test('shows KPI tiles with correct data', function () {
    $user = $this->createUserWithRole($this->store, 'admin');

    Order::factory()->count(5)->create([
        'store_id' => $this->store->id,
        'currency' => 'USD',
        'placed_at' => now(),
    ])->each(fn (Order $order) => $order->forceFill([
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
    ])->save());

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin')
        ->assertOk()
        ->assertSee('250.00 USD');

    $this->bindStore($this->store);

    Livewire::actingAs($user);
    Livewire::test(Dashboard::class)
        ->assertViewHas('ordersCount', 5)
        ->assertViewHas('totalSales', 25000)
        ->assertViewHas('averageOrderValue', 5000);
});

test('restricts dashboard to authenticated admins', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('restricts dashboard to store members', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin')
        ->assertForbidden();
});

test('all store roles can view the dashboard', function (string $role) {
    $user = $this->createUserWithRole($this->store, $role);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin')
        ->assertOk();
})->with(['owner', 'admin', 'staff', 'support']);

test('filters metrics by date range', function () {
    $user = $this->createUserWithRole($this->store, 'owner');

    Order::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(2),
        'total_amount' => 1000,
    ]);

    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'placed_at' => now()->subDays(45),
        'total_amount' => 2000,
    ]);

    $this->bindStore($this->store);

    Livewire::actingAs($user);
    Livewire::test(Dashboard::class)
        ->assertViewHas('ordersCount', 2)
        ->set('dateRange', 90)
        ->assertViewHas('ordersCount', 5)
        ->assertViewHas('totalSales', 8000);
});

test('shows the ten most recent orders', function () {
    $user = $this->createUserWithRole($this->store, 'owner');

    foreach (range(1, 12) as $i) {
        Order::factory()->create([
            'store_id' => $this->store->id,
            'order_number' => '#'.str_pad((string) (3000 + $i), 4, '0', STR_PAD_LEFT),
            'placed_at' => now()->subMinutes(12 - $i),
        ]);
    }

    $this->bindStore($this->store);

    Livewire::actingAs($user);
    Livewire::test(Dashboard::class)
        ->assertSee('#3012')
        ->assertSee('#3003')
        ->assertDontSee('#3001')
        ->assertDontSee('#3002');
});

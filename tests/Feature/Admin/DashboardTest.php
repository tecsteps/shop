<?php

use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->actingAs($this->user);
    session()->put('current_store_id', $this->store->id);
});

it('renders dashboard for authenticated admin user', function () {
    $response = $this->get('/admin');

    $response->assertSuccessful();
    $response->assertSee('Dashboard');
});

it('redirects unauthenticated users to admin login', function () {
    auth()->logout();

    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('displays KPI tiles with order data', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => now()->toIso8601String(),
    ]);

    Livewire::test(\App\Livewire\Admin\Dashboard::class)
        ->assertSee('Total Sales')
        ->assertSee('Orders');
});

it('filters dashboard by date range', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 10000,
        'placed_at' => now()->toIso8601String(),
    ]);

    $component = Livewire::test(\App\Livewire\Admin\Dashboard::class);

    $component->set('dateRange', 'today')
        ->assertHasNoErrors();

    $component->set('dateRange', 'last_7_days')
        ->assertHasNoErrors();

    $component->set('dateRange', 'last_30_days')
        ->assertHasNoErrors();
});

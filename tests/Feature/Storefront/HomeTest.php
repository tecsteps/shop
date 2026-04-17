<?php

use App\Livewire\Storefront\Home;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('renders the storefront home Livewire component', function (): void {
    Livewire::test(Home::class)
        ->assertStatus(200)
        ->assertSee('Thoughtfully made')
        ->assertSee('New arrivals');
});

it('responds from the / route with a 200', function (): void {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Thoughtfully made', false);
});

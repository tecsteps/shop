<?php

use App\Enums\ProductStatus;
use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function searchSetup(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);
    session(['current_store_id' => $store->getKey()]);
    app()->instance('current_store', $store);

    return [$store, $user];
}

it('delegates 2+ char queries to SearchService', function () {
    [$store, $user] = searchSetup();
    $matching = Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Matching product',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Other thing',
        'status' => ProductStatus::Active,
    ]);

    $fake = Mockery::mock(SearchService::class);
    $fake->shouldReceive('search')
        ->once()
        ->withArgs(function ($s, string $q, array $filters = [], $session = null, bool $log = false) use ($store): bool {
            return $s->getKey() === $store->getKey() && $q === 'match' && $log === false;
        })
        ->andReturn(new \Illuminate\Database\Eloquent\Collection([$matching]));

    app()->instance(SearchService::class, $fake);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Admin\Products\Index::class)
        ->set('search', 'match')
        ->assertSee('Matching product')
        ->assertDontSee('Other thing');
});

it('falls back to paginated list for queries shorter than 2 chars', function () {
    [$store, $user] = searchSetup();
    Product::factory()->create(['store_id' => $store->getKey(), 'title' => 'Alpha']);
    Product::factory()->create(['store_id' => $store->getKey(), 'title' => 'Bravo']);

    $fake = Mockery::mock(SearchService::class);
    $fake->shouldNotReceive('search');
    app()->instance(SearchService::class, $fake);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Admin\Products\Index::class)
        ->set('search', 'a')
        ->assertSee('Alpha')
        ->assertSee('Bravo');
});

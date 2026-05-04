<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminAnalyticsStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminAnalyticsUser(?StoreUserRole $role = null): User
{
    $store = adminAnalyticsStore();
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => ($role ?? StoreUserRole::Owner)->value,
        'created_at' => now(),
    ]);

    return $user;
}

test('admin analytics route renders metrics for store staff and above', function (): void {
    $this->actingAs(adminAnalyticsUser(StoreUserRole::Staff))
        ->get('/admin/analytics')
        ->assertSuccessful()
        ->assertSee('Analytics')
        ->assertSee('Total sales')
        ->assertSee('Top referrers');
});

test('admin analytics rejects support users', function (): void {
    $this->actingAs(adminAnalyticsUser(StoreUserRole::Support))
        ->get('/admin/analytics')
        ->assertForbidden();
});

test('admin analytics component filters and exports csv data', function (): void {
    $store = adminAnalyticsStore();
    app()->instance('current_store', $store);

    Livewire::actingAs(adminAnalyticsUser(StoreUserRole::Admin))
        ->test(AnalyticsIndex::class)
        ->assertSee('Sales over time')
        ->set('dateRange', 'last_7_days')
        ->set('channelFilter', 'storefront')
        ->set('deviceFilter', 'mobile')
        ->call('exportCsv')
        ->assertSet('isExporting', false)
        ->assertSet('exportUrl', fn (?string $url): bool => str_starts_with((string) $url, 'data:text/csv'));
});

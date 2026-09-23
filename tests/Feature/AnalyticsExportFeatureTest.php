<?php

use App\Jobs\GenerateAnalyticsExport;
use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsExport;
use App\Models\Order;
use App\Models\User;
use App\Services\AnalyticsReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-09-23 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('queues, tracks, and downloads a store-scoped analytics CSV export', function () {
    Queue::fake();
    Storage::fake('local');
    $store = shopStore();
    $otherStore = shopStore('analytics-export-other.test');
    $owner = User::factory()->create();
    $owner->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    app()->instance('current_store', $store);
    Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#AN-EXPORT-1',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'currency' => 'EUR',
        'total_amount' => 1299,
        'placed_at' => '2026-09-23 10:00:00',
    ]);
    Order::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'order_number' => '#AN-EXPORT-FOREIGN',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'currency' => 'EUR',
        'total_amount' => 900000,
        'placed_at' => '2026-09-23 10:00:00',
    ]);

    $component = Livewire::actingAs($owner)->test(Index::class)
        ->set('dateRange', 'custom')
        ->set('customStartDate', '2026-09-23')
        ->set('customEndDate', '2026-09-23')
        ->call('exportCsv')
        ->assertSet('isExporting', true)
        ->assertSet('exportUrl', null);

    $export = AnalyticsExport::query()->where('store_id', $store->id)->firstOrFail();
    expect($export->status)->toBe('queued');
    Queue::assertPushed(GenerateAnalyticsExport::class, fn (GenerateAnalyticsExport $job): bool => $job->exportId === $export->id
        && $job->connection === 'database');

    (new GenerateAnalyticsExport((int) $export->id))->handle(app(AnalyticsReportingService::class));
    $export->refresh();
    Storage::disk('local')->assertExists($export->storage_key);
    expect($export->status)->toBe('completed')
        ->and(Storage::disk('local')->get($export->storage_key))->toContain('2026-09-23,1299,EUR,1')
        ->and(Storage::disk('local')->get($export->storage_key))->not->toContain('900000');

    $downloadUrl = route('admin.analytics.exports.download', ['analyticsExport' => $export->id]);
    $component->call('pollExport')
        ->assertSet('isExporting', false)
        ->assertSet('exportUrl', $downloadUrl)
        ->assertSee('Download CSV');

    $download = $this->withSession(['current_store_id' => $store->id])->actingAs($owner)->get($downloadUrl)->assertOk();
    expect($download->streamedContent())->toContain('2026-09-23,1299,EUR,1')
        ->and($download->headers->get('content-type'))->toContain('text/csv');
});

it('denies analytics exports to support staff and hides exports from another selected store', function () {
    Storage::fake('local');
    $store = shopStore();
    $otherStore = shopStore('analytics-export-foreign.test');
    $owner = User::factory()->create();
    $support = User::factory()->create();
    $owner->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $support->stores()->attach($store->id, ['role' => 'support']);
    app()->instance('current_store', $store);
    $foreignExport = AnalyticsExport::factory()->create([
        'store_id' => $otherStore->id,
        'status' => 'completed',
        'storage_key' => 'analytics-exports/'.$otherStore->id.'/1.csv',
    ]);
    Storage::disk('local')->put($foreignExport->storage_key, "private\n");

    Livewire::actingAs($support)->test(Index::class)->assertForbidden();

    $this->withSession(['current_store_id' => $store->id])->actingAs($owner)->get(route('admin.analytics.exports.download', ['analyticsExport' => $foreignExport->id]))
        ->assertNotFound();
});

it('ends the export polling state and shows an error after a failed job', function () {
    $store = shopStore();
    $owner = User::factory()->create();
    $owner->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);
    $export = AnalyticsExport::factory()->create([
        'store_id' => $store->id,
        'status' => 'failed',
        'error_message' => 'storage unavailable',
    ]);

    Livewire::actingAs($owner)->test(Index::class)
        ->set('exportId', $export->id)
        ->set('isExporting', true)
        ->call('pollExport')
        ->assertSet('isExporting', false)
        ->assertSet('exportError', 'The analytics export could not be generated. Try again.')
        ->assertSee('The analytics export could not be generated. Try again.');
});

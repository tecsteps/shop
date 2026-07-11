<?php

use App\Models\Concerns\BelongsToStore;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

class TenantRecordForTest extends Model
{
    use BelongsToStore;

    protected $table = 'tenant_records';

    protected $fillable = ['store_id', 'name'];
}

beforeEach(function () {
    app()->forgetInstance('current_store');

    Schema::create('tenant_records', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('store_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    app()->forgetInstance('current_store');
});

test('tenant models are automatically assigned to the current store', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $record = TenantRecordForTest::query()->create(['name' => 'Scoped record']);

    expect($record->store_id)->toBe($store->id);
});

test('tenant model queries only return records for the current store', function () {
    [$firstStore, $secondStore] = Store::factory()->count(2)->create();

    TenantRecordForTest::withoutGlobalScope(StoreScope::class)->create([
        'store_id' => $firstStore->id,
        'name' => 'First store record',
    ]);
    TenantRecordForTest::withoutGlobalScope(StoreScope::class)->create([
        'store_id' => $secondStore->id,
        'name' => 'Second store record',
    ]);

    app()->instance('current_store', $firstStore);

    expect(TenantRecordForTest::query()->pluck('name')->all())
        ->toBe(['First store record'])
        ->and(TenantRecordForTest::withoutGlobalScope(StoreScope::class)->count())->toBe(2);
});

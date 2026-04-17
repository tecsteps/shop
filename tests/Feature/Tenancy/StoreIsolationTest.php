<?php

use App\Models\Concerns\BelongsToStore;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('widgets', function ($table): void {
        $table->id();
        $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('widgets');
    app()->forgetInstance('current_store');
});

it('applies store scope to models using BelongsToStore', function (): void {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    app()->instance('current_store', $storeA);
    Widget::create(['name' => 'A1']);
    Widget::create(['name' => 'A2']);

    app()->instance('current_store', $storeB);
    Widget::create(['name' => 'B1']);

    app()->instance('current_store', $storeA);
    expect(Widget::count())->toBe(2)
        ->and(Widget::pluck('name')->all())->toEqual(['A1', 'A2']);

    app()->instance('current_store', $storeB);
    expect(Widget::count())->toBe(1)
        ->and(Widget::pluck('name')->all())->toEqual(['B1']);
});

it('auto-assigns store_id on creating when current store is bound', function (): void {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $widget = Widget::create(['name' => 'auto']);

    expect($widget->store_id)->toBe($store->id);
});

class Widget extends Model
{
    use BelongsToStore;

    protected $table = 'widgets';

    protected $fillable = ['name', 'store_id'];
}

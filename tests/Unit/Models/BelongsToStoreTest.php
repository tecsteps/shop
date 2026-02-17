<?php

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

test('StoreScope applies where clause when current_store is bound', function () {
    $store = new stdClass;
    $store->id = 42;
    app()->instance('current_store', $store);

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('getTable')->andReturn('products');

    $builder = Mockery::mock(Builder::class);
    $builder->shouldReceive('where')
        ->with('products.store_id', 42)
        ->once()
        ->andReturnSelf();

    $scope = new StoreScope;
    $scope->apply($builder, $model);
});

test('StoreScope does not apply when current_store is not bound', function () {
    app()->forgetInstance('current_store');

    $model = Mockery::mock(Model::class);
    $builder = Mockery::mock(Builder::class);
    $builder->shouldNotReceive('where');

    $scope = new StoreScope;
    $scope->apply($builder, $model);
});

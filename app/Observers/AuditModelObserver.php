<?php

namespace App\Observers;

use App\Models\Collection;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ShippingZone;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditModelObserver
{
    /**
     * @var array<class-string<Model>, string>
     */
    private array $resourceTypes = [
        Product::class => 'product',
        Collection::class => 'collection',
        Discount::class => 'discount',
        Page::class => 'page',
        Theme::class => 'theme',
        Order::class => 'order',
        Fulfillment::class => 'fulfillment',
        Refund::class => 'refund',
        NavigationMenu::class => 'navigation_menu',
        ShippingZone::class => 'shipping_zone',
        StoreSettings::class => 'store_settings',
        TaxSettings::class => 'tax_setting',
    ];

    public function created(Model $model): void
    {
        $this->write($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->write($model, 'updated', $this->changes($model));
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->write($model, 'restored');
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    private function write(Model $model, string $action, ?array $changes = null): void
    {
        $resourceType = $this->resourceTypes[$model::class] ?? null;

        if ($resourceType === null) {
            return;
        }

        app(AuditLogger::class)->log(
            event: $model instanceof StoreSettings && $action === 'updated'
                ? 'store.settings_changed'
                : "{$resourceType}.{$action}",
            userId: auth()->id(),
            storeId: $this->storeId($model),
            resourceType: $resourceType,
            resourceId: is_numeric($model->getKey()) ? (int) $model->getKey() : null,
            changes: $changes,
        );
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function changes(Model $model): array
    {
        return collect($model->getChanges())
            ->except(['updated_at'])
            ->mapWithKeys(fn (mixed $newValue, string $key): array => [
                $key => [$model->getOriginal($key), $newValue],
            ])
            ->all();
    }

    private function storeId(Model $model): ?int
    {
        $storeId = $model->getAttribute('store_id');

        if (is_numeric($storeId)) {
            return (int) $storeId;
        }

        if (app()->bound('current_store')) {
            $store = app('current_store');

            return $store instanceof \App\Models\Store ? $store->getKey() : null;
        }

        return null;
    }
}

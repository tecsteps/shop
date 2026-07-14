<?php

namespace App\Observers;

use App\Models\Fulfillment;
use App\Models\Refund;
use App\Services\AuditLogger;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class AuditableObserver
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = [];
        foreach (Arr::except($model->getChanges(), ['updated_at']) as $attribute => $value) {
            if ($this->sensitive($attribute)) {
                continue;
            }
            $changes[$attribute] = [
                $this->serialise($model->getRawOriginal($attribute)),
                $this->serialise($value),
            ];
        }

        if ($changes !== []) {
            $this->record($model, 'updated', ['changes' => $changes]);
        }
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored');
    }

    /** @param array<string, mixed> $extra */
    private function record(Model $model, string $action, array $extra = []): void
    {
        $resource = match (class_basename($model)) {
            'NavigationMenu' => 'navigation_menu',
            'TaxSettings' => 'tax_setting',
            default => Str::snake(class_basename($model)),
        };

        $this->audit->log(
            "{$resource}.{$action}",
            auth()->guard('web')->id(),
            $this->storeId($model),
            $resource,
            is_numeric($model->getKey()) ? (int) $model->getKey() : null,
            $extra,
        );
    }

    private function storeId(Model $model): ?int
    {
        $storeId = $model->getAttribute('store_id');
        if ($storeId !== null) {
            return (int) $storeId;
        }

        if ($model instanceof Fulfillment || $model instanceof Refund) {
            return (int) ($model->order()->withoutGlobalScopes()->value('store_id') ?: 0) ?: null;
        }

        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return (int) ($store instanceof Model ? $store->getKey() : $store);
    }

    private function sensitive(string $attribute): bool
    {
        return Str::contains(mb_strtolower($attribute), ['password', 'token', 'secret', 'encrypted', 'raw_json']);
    }

    private function serialise(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return is_scalar($value) || $value === null ? $value : json_decode(json_encode($value), true);
    }
}

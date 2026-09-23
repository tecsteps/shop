<?php

namespace App\Providers;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Page;
use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\StoreUser;
use App\Models\TaxSetting;
use App\Models\Theme;
use App\Models\User;
use App\Services\AuditLogger;
use DateTimeInterface;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;

            if ($user instanceof User && $event->guard === 'web') {
                app(AuditLogger::class)->log('auth.login', (int) $user->getKey(), $this->currentStoreId());
            } elseif ($user instanceof Customer && $event->guard === 'customer') {
                app(AuditLogger::class)->log('customer.login', null, (int) $user->store_id, 'customer', (int) $user->getKey());
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $email = $event->credentials['email'] ?? $event->credentials['username'] ?? null;

            if ($event->guard === 'web') {
                app(AuditLogger::class)->log('auth.failed_login', null, $this->currentStoreId(), extra: ['email' => is_string($email) ? $email : null]);
            } elseif ($event->guard === 'customer') {
                app(AuditLogger::class)->log('customer.failed_login', null, $this->currentStoreId(), 'customer', extra: ['email' => is_string($email) ? $email : null]);
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->guard === 'web' && $event->user instanceof User) {
                app(AuditLogger::class)->log('auth.logout', (int) $event->user->getKey(), $this->currentStoreId());
            }
        });

        $this->registerModelAuditing();
    }

    /** @return array<class-string<Model>, string> */
    private function auditableModels(): array
    {
        return [
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
            TaxSetting::class => 'tax_setting',
            StoreUser::class => 'staff',
        ];
    }

    private function registerModelAuditing(): void
    {
        foreach ($this->auditableModels() as $modelClass => $resourceType) {
            $modelClass::created(function (Model $model) use ($resourceType): void {
                $event = $resourceType === 'product' && $model->status === 'archived' ? 'product.deleted' : "{$resourceType}.created";
                app(AuditLogger::class)->log(
                    $event,
                    Auth::id(),
                    $this->modelStoreId($model),
                    $resourceType,
                    $this->resourceId($model),
                    ['attributes' => $this->keyAttributes($model)],
                );
            });

            $modelClass::updating(function (Model $model): void {
                $originals = [];

                foreach ($model->getDirty() as $key => $value) {
                    if (! $this->isSensitiveKey((string) $key)) {
                        $originals[$key] = $this->safeAuditValue($model->getOriginal($key));
                    }
                }

                $model->setRelation('__audit_original_values', $originals);
            });

            $modelClass::updated(function (Model $model) use ($resourceType): void {
                $originals = $model->getRelation('__audit_original_values') ?? [];
                $changes = [];

                foreach ($model->getChanges() as $key => $value) {
                    if ($key !== 'updated_at' && ! $this->isSensitiveKey((string) $key)) {
                        $changes[$key] = [$originals[$key] ?? null, $this->safeAuditValue($value)];
                    }
                }

                $model->unsetRelation('__audit_original_values');

                if ($changes === []) {
                    return;
                }

                $event = $resourceType === 'product' && $model->status === 'archived' ? 'product.deleted' : "{$resourceType}.updated";
                app(AuditLogger::class)->log($event, Auth::id(), $this->modelStoreId($model), $resourceType, $this->resourceId($model), ['changes' => $changes]);
            });

            $modelClass::deleted(function (Model $model) use ($resourceType): void {
                app(AuditLogger::class)->log("{$resourceType}.deleted", Auth::id(), $this->modelStoreId($model), $resourceType, $this->resourceId($model));
            });

            if (method_exists($modelClass, 'restored')) {
                $modelClass::restored(function (Model $model) use ($resourceType): void {
                    app(AuditLogger::class)->log("{$resourceType}.restored", Auth::id(), $this->modelStoreId($model), $resourceType, $this->resourceId($model));
                });
            }
        }

        Customer::created(function (Customer $customer): void {
            app(AuditLogger::class)->log('customer.registered', null, (int) $customer->store_id, 'customer', (int) $customer->getKey());
        });

        PersonalAccessToken::created(function (PersonalAccessToken $token): void {
            app(AuditLogger::class)->log('api_token.created', Auth::id(), (int) $token->store_id, 'api_token', (int) $token->getKey(), [
                'token_name' => $token->name,
                'abilities' => $token->abilities,
            ]);
        });

        PersonalAccessToken::deleted(function (PersonalAccessToken $token): void {
            app(AuditLogger::class)->log('api_token.revoked', Auth::id(), (int) $token->store_id, 'api_token', (int) $token->getKey(), ['token_name' => $token->name]);
        });

        StoreSettings::updating(function (StoreSettings $settings): void {
            $settings->setRelation('__audit_original_settings', $this->safeAuditValue($settings->getOriginal('settings_json')));
        });

        StoreSettings::updated(function (StoreSettings $settings): void {
            app(AuditLogger::class)->log('store.settings_changed', Auth::id(), (int) $settings->store_id, 'store', (int) $settings->store_id, [
                'changes' => ['settings_json' => [$settings->getRelation('__audit_original_settings'), $this->safeAuditValue($settings->settings_json)]],
            ]);
            $settings->unsetRelation('__audit_original_settings');
        });
    }

    private function currentStoreId(): ?int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($store instanceof Store) {
            return (int) $store->getKey();
        }

        $storeId = request()->hasSession() ? request()->session()->get('current_store_id') : null;

        return is_numeric($storeId) ? (int) $storeId : null;
    }

    private function modelStoreId(Model $model): ?int
    {
        if ($model->getAttribute('store_id') !== null) {
            return (int) $model->getAttribute('store_id');
        }

        if (method_exists($model, 'order')) {
            $storeId = $model->order()->value('store_id');

            if (is_numeric($storeId)) {
                return (int) $storeId;
            }
        }

        return $this->currentStoreId();
    }

    private function resourceId(Model $model): ?int
    {
        $id = $model->getKey() ?? $model->getAttribute('user_id');

        return is_numeric($id) ? (int) $id : null;
    }

    /** @return array<string, mixed> */
    private function keyAttributes(Model $model): array
    {
        $keys = ['title', 'handle', 'name', 'code', 'status', 'order_number', 'total_amount', 'role'];
        $attributes = [];

        foreach ($keys as $key) {
            if ($model->getAttribute($key) !== null && ! $this->isSensitiveKey($key)) {
                $attributes[$key] = $this->safeAuditValue($model->getAttribute($key));
            }
        }

        return $attributes;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/password|secret|token/i', $key) === 1;
    }

    private function safeAuditValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            $safe = [];

            foreach ($value as $key => $item) {
                $safe[$key] = $this->isSensitiveKey((string) $key) ? '[redacted]' : $this->safeAuditValue($item);
            }

            return $safe;
        }

        return is_scalar($value) || $value === null ? $value : (string) $value;
    }
}

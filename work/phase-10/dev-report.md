# Phase 10: Apps and Webhooks - Dev Report

## Summary

Phase 10 implements the Apps and Webhooks infrastructure: 6 database tables, 6 Eloquent models with factories, 4 enums, the WebhookService with HMAC signing/verification, the DeliverWebhook queued job with retry logic and circuit breaker, and 2 admin pages (Apps and Developers).

## Files Created

### Migrations (6)
- `database/migrations/2026_03_20_140000_create_apps_table.php`
- `database/migrations/2026_03_20_140001_create_app_installations_table.php`
- `database/migrations/2026_03_20_140002_create_oauth_clients_table.php`
- `database/migrations/2026_03_20_140003_create_oauth_tokens_table.php`
- `database/migrations/2026_03_20_140004_create_webhook_subscriptions_table.php`
- `database/migrations/2026_03_20_140005_create_webhook_deliveries_table.php`

### Models (6)
- `app/Models/App.php`
- `app/Models/AppInstallation.php`
- `app/Models/OauthClient.php`
- `app/Models/OauthToken.php`
- `app/Models/WebhookSubscription.php`
- `app/Models/WebhookDelivery.php`

### Enums (4)
- `app/Enums/AppStatus.php`
- `app/Enums/AppInstallationStatus.php`
- `app/Enums/WebhookSubscriptionStatus.php`
- `app/Enums/WebhookDeliveryStatus.php`

### Factories (6)
- `database/factories/AppFactory.php`
- `database/factories/AppInstallationFactory.php`
- `database/factories/OauthClientFactory.php`
- `database/factories/OauthTokenFactory.php`
- `database/factories/WebhookSubscriptionFactory.php`
- `database/factories/WebhookDeliveryFactory.php`

### Service & Job
- `app/Services/WebhookService.php` - dispatch(), sign(), verify()
- `app/Jobs/DeliverWebhook.php` - HTTP POST with HMAC headers, exponential backoff, circuit breaker

### Admin Pages
- `app/Livewire/Admin/Apps/Index.php` + Blade view
- `app/Livewire/Admin/Developers/Index.php` + Blade view

### Tests
- `tests/Feature/Webhooks/WebhookSignatureTest.php` (4 tests)
- `tests/Feature/Webhooks/WebhookDeliveryTest.php` (6 tests)

## Files Modified
- `app/Models/Store.php` - Added appInstallations() and webhookSubscriptions() relationships
- `routes/web.php` - Added admin.apps.index and admin.developers.index routes
- `resources/views/layouts/admin.blade.php` - Added Apps and Developers sidebar nav items

## Test Results

```
Tests:    553 passed (1057 assertions)
Duration: 11.34s
```

All 553 tests pass including 10 new webhook tests. Zero failures, zero regressions.

## Pint Results

All PHP files pass code style checks after `vendor/bin/pint --dirty`.

## Key Decisions

1. **Encrypted cast for secrets**: `signing_secret_encrypted` and `client_secret_encrypted` use Laravel's `encrypted` cast, which auto-encrypts on write and decrypts on read. This means factory/test code passes the raw secret value, not `encrypt()`.

2. **Circuit breaker via delivery history**: Rather than maintaining a separate failure counter column, the circuit breaker counts consecutive failures by querying the most recent 5 deliveries for the subscription. This avoids race conditions with a mutable counter.

3. **Stub admin pages**: The Apps and Developers pages are functional but intentionally minimal per the task scope. They show installed apps and webhook subscriptions with proper empty states.

4. **WebhookSubscription uses BelongsToStore trait**: Per the spec, webhook_subscriptions are tenant-scoped via the store_id column and use the global StoreScope.

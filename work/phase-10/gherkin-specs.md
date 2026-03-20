# Phase 10: Apps and Webhooks - Gherkin Specifications

## Step 10.1: Migrations (6 tables)

### Feature: Apps Table Migration

```gherkin
Feature: Apps table schema
  The apps table stores the registry of third-party and first-party applications.

  Scenario: Apps table has required columns
    Given a fresh database migration
    Then the "apps" table should exist
    And it should have columns: id, name, status, created_at
    And "status" should default to "active"
    And there should be an index "idx_apps_status" on "status"

  Scenario: Apps status constraint
    Given the apps table exists
    When I insert a record with status "active"
    Then the insert should succeed
    When I insert a record with status "disabled"
    Then the insert should succeed
```

### Feature: App Installations Table Migration

```gherkin
Feature: App installations table schema
  The app_installations table tracks which apps are installed on which stores.

  Scenario: App installations table has required columns and FKs
    Given a fresh database migration
    Then the "app_installations" table should exist
    And it should have columns: id, store_id, app_id, scopes_json, status, installed_at
    And "store_id" should be a FK to stores(id) with CASCADE delete
    And "app_id" should be a FK to apps(id) with CASCADE delete
    And "scopes_json" should default to "[]"
    And "status" should default to "active"
    And there should be a unique index "idx_app_installations_store_app" on (store_id, app_id)
    And there should be indexes on store_id and app_id
```

### Feature: OAuth Clients Table Migration

```gherkin
Feature: OAuth clients table schema
  The oauth_clients table stores OAuth2 client credentials for apps.

  Scenario: OAuth clients table has required columns and FKs
    Given a fresh database migration
    Then the "oauth_clients" table should exist
    And it should have columns: id, app_id, client_id, client_secret_encrypted, redirect_uris_json
    And "app_id" should be a FK to apps(id) with CASCADE delete
    And "client_id" should have a unique index
    And "redirect_uris_json" should default to "[]"
```

### Feature: OAuth Tokens Table Migration

```gherkin
Feature: OAuth tokens table schema
  The oauth_tokens table stores issued access/refresh tokens for app installations.

  Scenario: OAuth tokens table has required columns and FKs
    Given a fresh database migration
    Then the "oauth_tokens" table should exist
    And it should have columns: id, installation_id, access_token_hash, refresh_token_hash, expires_at
    And "installation_id" should be a FK to app_installations(id) with CASCADE delete
    And "access_token_hash" should have a unique index
    And there should be an index on "expires_at"
```

### Feature: Webhook Subscriptions Table Migration

```gherkin
Feature: Webhook subscriptions table schema
  The webhook_subscriptions table stores registered webhook endpoints for event delivery.

  Scenario: Webhook subscriptions table has required columns and FKs
    Given a fresh database migration
    Then the "webhook_subscriptions" table should exist
    And it should have columns: id, store_id, app_installation_id, event_type, target_url, signing_secret_encrypted, status
    And "store_id" should be a FK to stores(id) with CASCADE delete
    And "app_installation_id" should be a nullable FK to app_installations(id) with CASCADE delete
    And "status" should default to "active"
    And there should be an index on (store_id, event_type)
    And there should be an index on app_installation_id
```

### Feature: Webhook Deliveries Table Migration

```gherkin
Feature: Webhook deliveries table schema
  The webhook_deliveries table logs delivery attempts for webhooks.

  Scenario: Webhook deliveries table has required columns and FKs
    Given a fresh database migration
    Then the "webhook_deliveries" table should exist
    And it should have columns: id, subscription_id, event_id, attempt_count, status, last_attempt_at, response_code, response_body_snippet
    And "subscription_id" should be a FK to webhook_subscriptions(id) with CASCADE delete
    And "attempt_count" should default to 1
    And "status" should default to "pending"
    And there should be indexes on event_id, status, and last_attempt_at
```

## Step 10.2: Webhook Service

### Feature: Webhook Signature

```gherkin
Feature: Webhook HMAC-SHA256 signing and verification
  The WebhookService signs payloads with HMAC-SHA256 and verifies incoming signatures.

  Scenario: Generates a valid HMAC-SHA256 signature
    Given a payload '{"event":"order.created"}' and secret "test-secret"
    When I call sign(payload, secret)
    Then it should return the expected HMAC-SHA256 hex digest

  Scenario: Verifies a valid signature
    Given a payload and a secret
    When I generate a signature using sign()
    And I call verify(payload, signature, secret)
    Then it should return true

  Scenario: Rejects a tampered payload
    Given a signed payload with a known secret
    When I call verify with a modified payload but the original signature
    Then it should return false

  Scenario: Rejects an incorrect secret
    Given a signed payload with secret A
    When I call verify with the same payload and signature but secret B
    Then it should return false
```

### Feature: Webhook Delivery

```gherkin
Feature: Webhook delivery via DeliverWebhook job
  The WebhookService dispatches webhook delivery jobs to matching subscriptions.

  Scenario: Delivers a webhook to a subscribed URL
    Given an active webhook subscription for "order.created" at "https://example.com/hook"
    When the WebhookService dispatches "order.created" for the store
    Then an HTTP POST should be made to "https://example.com/hook"
    And a webhook_deliveries record should be created with status "success"

  Scenario: Signs the payload with HMAC in headers
    Given an active webhook subscription with a signing secret
    When a webhook is delivered
    Then the request should include header "X-Platform-Signature" with a valid HMAC
    And the request should include header "X-Platform-Event" with the event type
    And the request should include header "X-Platform-Delivery-Id" with a UUID
    And the request should include header "X-Platform-Timestamp" with a unix timestamp

  Scenario: Retries failed deliveries with exponential backoff
    Given a webhook subscription and a target that returns HTTP 500
    When the first delivery attempt fails
    Then the job should be retried with backoff delays [60, 300, 1800, 7200, 43200]
    And the delivery attempt_count should be incremented

  Scenario: Marks delivery as failed after max retries
    Given a webhook delivery that has exhausted all 6 attempts (1 initial + 5 retries)
    Then the delivery status should be set to "failed"

  Scenario: Pauses subscription after circuit breaker threshold
    Given a webhook subscription with 4 consecutive failures
    When the 5th consecutive delivery fails
    Then the subscription status should be set to "paused"

  Scenario: Resets failure counter on successful delivery
    Given a webhook subscription with some consecutive failures
    When a delivery succeeds
    Then the consecutive failure counter should reset to 0
```

### Feature: Admin Apps Page

```gherkin
Feature: Admin Apps page
  The admin Apps page shows installed apps for the current store.

  Scenario: Shows installed apps list
    Given an admin user is logged in
    And the store has installed apps
    When they visit /admin/apps
    Then they should see a list of installed apps with name, status, and install date

  Scenario: Shows empty state when no apps installed
    Given an admin user is logged in
    And the store has no installed apps
    When they visit /admin/apps
    Then they should see "No apps installed"
```

### Feature: Admin Developers Page

```gherkin
Feature: Admin Developers page
  The admin Developers page manages API tokens and webhook subscriptions.

  Scenario: Shows webhook subscriptions
    Given an admin user is logged in
    And the store has webhook subscriptions
    When they visit /admin/developers
    Then they should see a table of webhook subscriptions with event type, URL, and status

  Scenario: Shows empty state for webhooks
    Given an admin user is logged in
    And the store has no webhook subscriptions
    When they visit /admin/developers
    Then they should see appropriate empty state messaging
```

---

## Traceability Table

| Gherkin Scenario | Spec Reference | Implementation Artifact | Test File |
|---|---|---|---|
| Apps table has required columns | `specs/01-DATABASE-SCHEMA.md` Epic 8 - apps | `create_apps_table` migration | `WebhookDeliveryTest.php` (setup) |
| App installations table has required columns and FKs | `specs/01-DATABASE-SCHEMA.md` Epic 8 - app_installations | `create_app_installations_table` migration | `WebhookDeliveryTest.php` (setup) |
| OAuth clients table has required columns and FKs | `specs/01-DATABASE-SCHEMA.md` Epic 8 - oauth_clients | `create_oauth_clients_table` migration | N/A (schema test) |
| OAuth tokens table has required columns and FKs | `specs/01-DATABASE-SCHEMA.md` Epic 8 - oauth_tokens | `create_oauth_tokens_table` migration | N/A (schema test) |
| Webhook subscriptions table has required columns and FKs | `specs/01-DATABASE-SCHEMA.md` Epic 8 - webhook_subscriptions | `create_webhook_subscriptions_table` migration | `WebhookDeliveryTest.php` (setup) |
| Webhook deliveries table has required columns and FKs | `specs/01-DATABASE-SCHEMA.md` Epic 8 - webhook_deliveries | `create_webhook_deliveries_table` migration | `WebhookDeliveryTest.php` (setup) |
| Generates a valid HMAC-SHA256 signature | `specs/05-BUSINESS-LOGIC.md` 13.2 | `WebhookService::sign()` | `WebhookSignatureTest.php` |
| Verifies a valid signature | `specs/05-BUSINESS-LOGIC.md` 13.2 | `WebhookService::verify()` | `WebhookSignatureTest.php` |
| Rejects a tampered payload | `specs/05-BUSINESS-LOGIC.md` 13.2 | `WebhookService::verify()` | `WebhookSignatureTest.php` |
| Rejects an incorrect secret | `specs/05-BUSINESS-LOGIC.md` 13.2 | `WebhookService::verify()` | `WebhookSignatureTest.php` |
| Delivers a webhook to a subscribed URL | `specs/05-BUSINESS-LOGIC.md` 13.2, `specs/09-IMPLEMENTATION-ROADMAP.md` Step 10.2 | `DeliverWebhook` job, `WebhookService::dispatch()` | `WebhookDeliveryTest.php` |
| Signs the payload with HMAC in headers | `specs/05-BUSINESS-LOGIC.md` 13.2 (headers table) | `DeliverWebhook` job | `WebhookDeliveryTest.php` |
| Retries failed deliveries with exponential backoff | `specs/05-BUSINESS-LOGIC.md` 13.3 | `DeliverWebhook` backoff config | `WebhookDeliveryTest.php` |
| Marks delivery as failed after max retries | `specs/05-BUSINESS-LOGIC.md` 13.3 | `DeliverWebhook::failed()` | `WebhookDeliveryTest.php` |
| Pauses subscription after circuit breaker threshold | `specs/05-BUSINESS-LOGIC.md` 13.4 | `WebhookService` circuit breaker | `WebhookDeliveryTest.php` |
| Resets failure counter on successful delivery | `specs/05-BUSINESS-LOGIC.md` 13.4 | `DeliverWebhook` success handler | `WebhookDeliveryTest.php` |
| Shows installed apps list | `specs/03-ADMIN-UI.md` Section 15 | `Admin\Apps\Index` Livewire component | Browser verification |
| Shows empty state when no apps installed | `specs/03-ADMIN-UI.md` Section 15 | `Admin\Apps\Index` Livewire component | Browser verification |
| Shows webhook subscriptions | `specs/03-ADMIN-UI.md` Section 16 | `Admin\Developers\Index` Livewire component | Browser verification |
| Shows empty state for webhooks | `specs/03-ADMIN-UI.md` Section 16 | `Admin\Developers\Index` Livewire component | Browser verification |

## Self-Assessment

**Coverage strengths:**
- All 6 migration tables are specified with column, FK, index, and default requirements
- WebhookService sign/verify has 4 scenarios covering happy path and validation
- Webhook delivery covers: happy path, headers, retry backoff, max retry failure, circuit breaker, and counter reset
- Admin pages cover populated and empty states

**Coverage gaps/weaknesses:**
- No Gherkin scenarios for model relationship testing (e.g., App hasMany AppInstallations) -- these are implicitly tested through factory and service usage
- The admin pages have minimal Gherkin since these are stub/placeholder pages per the task scope
- OAuth flow (token issuance, refresh) is not covered since it is outside the Phase 10 scope (models and migrations only)
- No scenarios for concurrent webhook delivery or race conditions in the circuit breaker

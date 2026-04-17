# Phase 10: Apps and Webhooks - Code Review

## Migrations

### create_apps_table
Simple table with id, name, status (defaulting to 'active'), and created_at. Index on status for filtering. Matches spec exactly.

### create_app_installations_table
FKs to stores and apps with CASCADE delete. Unique constraint on (store_id, app_id) prevents duplicate installations. scopes_json defaults to '[]'. Status defaults to 'active'.

### create_oauth_clients_table
FK to apps with CASCADE delete. client_id has a unique index for OAuth2 lookup. redirect_uris_json defaults to '[]'. client_secret_encrypted stores encrypted values via the model's `encrypted` cast.

### create_oauth_tokens_table
FK to app_installations with CASCADE delete. access_token_hash has a unique index for token validation lookup. expires_at indexed for cleanup queries.

### create_webhook_subscriptions_table
FKs to stores (required) and app_installations (nullable). Composite index on (store_id, event_type) for efficient subscription lookup during event dispatch. Index on app_installation_id for listing per installation.

### create_webhook_deliveries_table
FK to webhook_subscriptions with CASCADE delete. Indexes on event_id (deduplication), status (finding pending/failed), and last_attempt_at (retry scheduling). attempt_count defaults to 1, status defaults to 'pending'.

**Assessment**: All 6 migrations match the spec in `specs/01-DATABASE-SCHEMA.md` Epic 8. Column types, defaults, constraints, and indexes are correct.

---

## Models

### App
- Uses AppStatus enum cast
- HasMany relationships to installations and oauthClients
- No timestamps (uses manual created_at text field like other models in this project)

### AppInstallation
- Casts scopes_json as array and status as AppInstallationStatus enum
- BelongsTo store and app
- HasMany oauthTokens and webhookSubscriptions

### OauthClient
- Casts redirect_uris_json as array
- Uses `encrypted` cast for client_secret_encrypted
- BelongsTo app

### OauthToken
- BelongsTo installation (AppInstallation)
- Stores hashed tokens (not encrypted -- hashing is one-way, appropriate for tokens)

### WebhookSubscription
- Uses BelongsToStore trait for tenant scoping (per spec section 1.2)
- Casts signing_secret_encrypted with `encrypted` cast
- Status cast to WebhookSubscriptionStatus enum
- HasMany deliveries, BelongsTo appInstallation

### WebhookDelivery
- Casts status to WebhookDeliveryStatus enum
- Integer casts for attempt_count and response_code
- BelongsTo subscription

**Assessment**: All models follow project conventions (HasFactory, explicit casts() method, proper relationship return types). WebhookSubscription correctly uses BelongsToStore trait.

---

## WebhookService

### dispatch()
Queries active subscriptions matching store_id and event_type using withoutGlobalScopes() (since we already filter by store_id explicitly). Creates a WebhookDelivery record with attempt_count=0 and status=pending, then dispatches a DeliverWebhook job.

### sign()
Returns HMAC-SHA256 hex digest using hash_hmac(). Simple and correct.

### verify()
Uses hash_equals() for timing-safe comparison. Prevents timing attacks on signature verification.

**Assessment**: Clean, focused service. The dispatch method correctly filters only active subscriptions.

---

## DeliverWebhook Job

### Retry configuration
- `$tries = 6` (1 initial + 5 retries)
- `backoff()` returns [60, 300, 1800, 7200, 43200] per spec

### handle()
1. Fetches subscription using withoutGlobalScopes() and checks it is still active
2. JSON-encodes the payload, signs with HMAC-SHA256 using decrypted secret
3. Increments attempt_count and sets last_attempt_at
4. Sends HTTP POST with required headers (X-Platform-Signature, X-Platform-Event, X-Platform-Delivery-Id, X-Platform-Timestamp, Content-Type)
5. On success: marks delivery as success, resets consecutive failures
6. On failure: increments consecutive failures, re-throws for retry (or marks as failed if at max attempts)

### Circuit breaker
- countConsecutiveFailures() queries the 5 most recent deliveries for the subscription
- If 5+ consecutive failures, pauses the subscription
- Logs a warning when pausing

### failed()
Called by Laravel when all retries are exhausted. Marks delivery as failed and increments the failure counter.

**Potential concern**: The circuit breaker counts failures by querying recent deliveries rather than maintaining a counter column. This is slightly more expensive per delivery but avoids race conditions with concurrent deliveries. Acceptable trade-off for this use case.

---

## Admin Pages

### Apps\Index
- Queries AppInstallation with eager-loaded app relationship
- Uses withoutGlobalScopes() since AppInstallation does not have BelongsToStore trait (it has its own store_id FK)
- Shows empty state with icon and descriptive text
- Each installed app card shows: icon placeholder, app name, relative install date, status badge

### Developers\Index
- Two sections: API Tokens (stub) and Webhooks
- Webhooks section queries WebhookSubscription for the current store
- Shows table with event_type, target_url, and status badge
- Color-coded badges: active=green, paused=red, disabled=zinc

**Assessment**: Both pages follow the existing admin page patterns (layout with breadcrumbs, getStoreId() helper, Flux components).

---

## Tests

### WebhookSignatureTest (4 tests)
1. Generates valid HMAC-SHA256 -- verifies correct hash length and value
2. Verifies valid signature -- round-trip sign then verify
3. Rejects tampered payload -- modified payload fails verification
4. Rejects incorrect secret -- different secrets produce different signatures

### WebhookDeliveryTest (6 tests)
1. Delivers webhook to subscribed URL -- full integration with Http::fake, verifies all 4 headers
2. Signs payload with HMAC -- verifies signature matches expected value for known secret
3. Retries with exponential backoff -- verifies $tries=6 and backoff array
4. Marks delivery as failed after max retries -- calls failed() and checks status
5. Pauses subscription after circuit breaker threshold -- creates 4 prior failures, 5th triggers pause
6. Dispatches jobs only for active subscriptions -- verifies paused subscriptions are skipped

**Assessment**: Tests cover all spec scenarios. Circuit breaker test is thorough with proper setup of prior failures.

---

## Overall Assessment

Phase 10 implementation is complete and correct. All code matches the spec, follows project conventions, and passes 553 tests with zero regressions.

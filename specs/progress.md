# Shop implementation progress

The implementation follows the build order in `09-IMPLEMENTATION-ROADMAP.md` and acceptance details in the other `specs/*.md` files. Each completed iteration is committed separately.

## Phases

- [ ] Foundation: tenant schema, store resolution, authentication, authorization
- [ ] Catalog: products, variants, inventory, collections, media
- [ ] Themes, pages, navigation, storefront layout
- [ ] Cart, checkout, discounts, shipping, taxes
- [ ] Payments, orders, refunds, fulfillment
- [ ] Customer accounts and addresses
- [ ] Admin panel and settings
- [ ] Search
- [ ] Analytics
- [ ] Apps, API tokens, and outbound webhooks
- [ ] Accessibility, security, responsive polish, and error handling
- [ ] Pest unit and feature coverage
- [ ] Playwright acceptance review and independent second-agent audit

## Iterations

### 2026-09-23 — Repository and requirements audit

- Confirmed the working tree was clean and the app was the Laravel starter kit; no shop implementation is present in this checkout.
- Read the roadmap and project-local instructions; `.ai/rules` does not exist.
- Confirmed Laravel 12.51, Livewire 4.1, Flux UI 2.12, Tailwind 4, and Pest 4.3 are installed. The Pest browser plugin and Sanctum are not installed.
- Existing Pest baseline passed: 33 tests, 75 assertions.
- Resolved the Herd URL as `http://shop.test`; Playwright MCP is available for the acceptance review.
- Delegated independent spec acceptance mapping and browser infrastructure inspection.

### 2026-09-23 — Initial shop foundation and storefront slice

- Added the tenant-aware commerce schema, store resolver, store-scoped models, role policies, catalog/cart/checkout/payment/order/refund/fulfillment services, storefront and admin route/component skeletons, API token middleware, and two-store demo seeding.
- Added the responsive storefront and admin Blade surfaces. The storefront supports the catalog, product detail, cart, checkout, account, search, page, and confirmation views; admin screens are being wired to complete data/actions in the next iteration.
- Added a newsletter subscription table/action and HTML sanitizer for merchant-authored page/theme content.
- Enabled `RefreshDatabase` for feature tests, restored the starter settings routes, and fixed full-page Livewire view composition uncovered by the first migrated test run.
- Chose `payment_selected` as the checkout reservation state, normalized `country_code`/`country` and `zip`/`postal_code` address aliases, used signed timestamp plus body for webhook HMAC, and followed the per-product seed matrix count of 117 variants.
- Known work remaining: complete missing admin actions/routes and password reset/logout flows, finish API request/response/ability contracts, add webhook delivery and analytics aggregation, add comprehensive shop tests, and run customer/admin Playwright scenarios plus independent acceptance audit.

**Checks this iteration:** fresh SQLite migration + demo seeding succeeded; initial Pest suite passed (33 tests, 75 assertions); Pint passed; admin Blade compilation and `npm run build` passed in the view agent's check.

### 2026-09-23 — Tenant contracts, checkout/account flows, analytics and webhooks

- Reconciled storefront API guest-cart access, cart-version requirements, discount allocation recalculation, checkout address aliases, digital-only checkout, payment retry, expiration, and API error codes with the route/business specs.
- Added store-scoped customer password reset through Laravel's password broker, a tenant-bound token repository/provider, generic reset-link acknowledgements, and admin/customer recovery pages. Removed Fortify's public registration and generic reset endpoints so they cannot bypass the shop-specific flows; made logout a CSRF-protected POST.
- Corrected tax rounding to calculate each item and taxable shipping line independently before summing, as required by the pricing rules.
- Added daily analytics aggregation and queued outbound webhook delivery with encrypted signing secrets, HMAC-SHA256 signatures, delivery history, retries, response snippets, and pause after five consecutive failures. Analytics schema now contains canonical event fields and completed-checkout counts.
- Added reusable model factories and focused feature/unit coverage for tenant resolution, catalog lifecycle, guest cart version/stock errors, checkout/payment/inventory transitions, customer registration/login/address ownership/reset isolation, admin API abilities, pricing/discount/tax rounding, HTML sanitization, analytics aggregation, and webhook delivery/signatures/retries.
- Progress phases remain unchecked until the remaining routes/admin actions, search/settings, media, scheduled cleanup, and browser acceptance work are finished.

**Checks this iteration:** 39 focused shop tests passed (156 assertions); `vendor/bin/pint --dirty --format agent` passed; `npm run build` passed; route list confirmed the new shop recovery endpoints and absence of Fortify's generic recovery routes.

### 2026-09-23 — Variant matrix, FTS, analytics ingestion, and maintenance

- Added a reusable option-matrix reconciler that preserves matching variant pricing, SKU, inventory, and order history while creating/removing combinations safely. Added focused coverage for combinations, preservation, archived variants, and invalid matrices.
- Added a forward migration that rebuilds SQLite FTS5 with the required `product_type` field and reindexes existing products; product saves now keep that field synchronized.
- Analytics ingestion now writes canonical `properties_json` and `occurred_at` fields as well as compatibility fields, accepts product-view events, and drops duplicate client event IDs per store.
- Outbound webhook signatures now bind the Unix event timestamp and JSON body as `timestamp.body`, as required by the security spec.
- Added scheduled jobs for 14-day abandoned cart cleanup, expired checkout inventory release, and configurable overdue bank-transfer cancellation. Scheduled cancellations also fail pending payments and emit `OrderCancelled`.
- Customer login, registration, and password-reset pages now resolve their storefront tenant from the hostname. Static page rendering now uses the sanitizer output rather than the stored HTML.
- Updated scaffold auth tests to assert the shop-specific admin/customer auth contract instead of the removed generic Fortify registration/reset routes.
- Remaining work includes wiring the variant matrix into admin/API product editing, media processing, missing admin CRUD/routes, token and webhook management UI, completing event-driven webhooks and trusted analytics, and running browser acceptance plus the final independent review.

**Checks this iteration:** full Pest suite passed (81 tests, 307 assertions); focused matrix/tenant/analytics/maintenance/auth regressions passed; Pint passed; `npm run build` passed; `php artisan schedule:list` shows all four documented schedules.

### 2026-09-23 — API route coverage and signed media upload

- Registered the collection and discount API write endpoints, applied checkout throttling to all checkout actions, and aligned API search/settings abilities with the token ability list.
- Added a signed product-media upload flow with configurable image/video size limits, extension/content-type matching, temporary upload URLs, streaming storage, one-time completion, and tenant/product authorization.
- Fixed FTS relevance retrieval to use SQLite's native `MATCH` query form; the synonym and stop-word search feature tests now pass.
- An independent API contract audit identified additional remaining API gaps. They are being addressed before final acceptance; the phase checklist remains open.

**Checks this iteration:** signed media API Pest tests passed (3 tests, 16 assertions); search settings Pest tests passed (4 tests, 19 assertions); focused Pint and `git diff --check` passed.

### 2026-09-23 — Discount application and fulfillment lifecycle

- Checkout recalculates totals before payment, applies eligible automatic promotions alongside one code discount, and records one-per-customer redemptions safely. Refund amounts reduce recognized order revenue.
- Fulfillment actions now preserve partial quantities and tracking data, keep pending shipments from completing orders, and complete an order only after all required quantities are delivered.
- Added admin order detail controls for creating partial shipments and marking shipments shipped or delivered.

**Checks this iteration:** focused discount, fulfillment, and pricing Pest tests passed (11 tests, 73 assertions); Pint passed on the changed PHP files.

### 2026-09-23 — Admin API contracts, exports, and tiered shipping

- Completed store-scoped product create/read/update/archive API behavior, including option matrices, variant positions, inventory, collections, partial updates, title/vendor/SKU search, and cross-store 404 isolation.
- Completed order list/detail, fulfillment, refund, tax configuration, search/analytics operations, and asynchronous order CSV exports. Export jobs run on the database queue, filter only the owning store's orders, store CSV files privately, and expose one-hour signed download URLs.
- Added shipping zone specificity and flat, weight-tier, and price-tier calculations. Physical-item weights exclude digital products, unavailable tiers are hidden, and checkout persists the selected calculated amount.
- Fixed variant-create position reconciliation so submitted positions map to stable variant IDs as the collection is reordered.
- Added regression coverage for export authorization/signature/filtering, full product and order API resources, tax provider settings, and shipping tier/zone calculations.

**Checks this iteration:** the combined focused admin API, checkout API, cart API, shipping, and discount run passed (52 tests, 512 assertions); `vendor/bin/pint --dirty --format agent` passed. Full-suite and browser acceptance remain pending.

## Verification log

| Date | Check | Result |
|------|-------|--------|
| 2026-09-23 | Existing Pest suite (`php artisan test --compact`) | Passed: 33 tests, 75 assertions |
| 2026-09-23 | Fresh migration + demo seed (`php artisan migrate:fresh --seed --force --no-interaction`) | Passed on SQLite; 2 stores, 117 fashion variants, and 18 seeded orders |
| 2026-09-23 | Pest after tenant test setup and Livewire view fixes | Passed: 33 tests, 75 assertions |
| 2026-09-23 | `vendor/bin/pint --dirty --format agent` | Passed |
| 2026-09-23 | Admin Blade compilation and `npm run build` | Passed in the view agent's check |
| 2026-09-23 | Focused tenant, catalog, cart, checkout, account, API, pricing, analytics, and webhook Pest tests | Passed: 39 tests, 156 assertions |
| 2026-09-23 | `npm run build` after account recovery views | Passed |
| 2026-09-23 | `php artisan route:list --path=forgot` and `--path=reset` | Passed; both admin and customer shop routes registered, generic Fortify reset routes removed |
| 2026-09-23 | Full Pest suite after variant, FTS, analytics ingestion, maintenance, and auth updates | Passed: 81 tests, 307 assertions |
| 2026-09-23 | `php artisan schedule:list --no-interaction` | Passed; analytics, abandoned-cart cleanup, checkout expiration, and bank-transfer cancellation schedules registered |
| 2026-09-23 | Signed product-media upload Pest tests | Passed: 3 tests, 16 assertions |
| 2026-09-23 | Search settings Pest tests | Passed: 4 tests, 19 assertions |
| 2026-09-23 | Discount, fulfillment lifecycle, and pricing Pest tests | Passed: 11 tests, 73 assertions |
| 2026-09-23 | Admin API, order export, checkout/cart APIs, shipping, and discount Pest tests | Passed: 52 tests, 512 assertions |

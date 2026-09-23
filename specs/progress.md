# Shop implementation progress

The implementation follows the build order in `09-IMPLEMENTATION-ROADMAP.md` and acceptance details in the other `specs/*.md` files. Each completed iteration is committed separately.

## Phases

- [x] Foundation: tenant schema, store resolution, authentication, authorization
- [x] Catalog: products, variants, inventory, collections, media
- [x] Themes, pages, navigation, storefront layout
- [x] Cart, checkout, discounts, shipping, taxes
- [x] Payments, orders, refunds, fulfillment
- [x] Customer accounts and addresses
- [x] Admin panel and settings
- [x] Search
- [x] Analytics
- [x] Apps, API tokens, and outbound webhooks
- [x] Accessibility, security, responsive polish, and error handling
- [x] Pest unit and feature coverage
- [ ] Playwright acceptance review and independent second-agent audit

The implementation phases are complete. Final acceptance review is still open while the second agent checks the integrated tree. Browser testing uses the Playwright MCP; the Pest browser plugin and the 143 browser-test files listed in `08-PLAYWRIGHT-E2E-PLAN.md` are not installed/present.

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

### 2026-09-23 — Admin product, discount, inventory, and search screens

- Expanded product editing to manage option matrices, per-variant pricing and stock, collections, safe formatted descriptions, and product status transitions.
- Added discount create/edit behavior for codes, automatic promotions, value types, schedules, eligibility, usage limits, and one-use-per-customer; the list now searches codes and filters active, scheduled, and expired promotions.
- Added store-scoped inventory search, stock filters, and permission-gated quantity/policy updates. Search settings now edit synonym groups and stop words and queue reindexing.
- Kept tax settings synchronized with the canonical tax configuration while preserving the admin form workflow.
- Added regression cases for XSS sanitization, compare-at pricing, discount search/status filters, inventory permissions, search settings, and variant preservation.

**Checks this iteration:** admin product/discount/inventory/search Pest tests passed (20 tests, 127 assertions); `npm run build`, `php artisan view:cache`, and Pint passed.

### 2026-09-23 — Checkout tax provider selection and fallback

- Added the documented tax-provider contract and request/result value objects, with manual regional tax calculation behind the contract.
- Checkout now selects manual tax, no-tax provider mode, or the Stripe Tax stub from store settings. The stub follows each store's allow/block fallback, logs allow-mode failures, and records a checkout tax-provider snapshot.
- Added the missing nullable checkout snapshot column with a forward migration.

**Checks this iteration:** provider, manual regional tax, and checkout regression Pest tests passed (12 tests, 47 assertions).

### 2026-09-23 — Free-shipping discount restoration

- Fixed checkout recalculation to resolve the selected shipping rate again, so removing a free-shipping discount restores the cart's selected shipping charge, including recalculated tier rates.
- Added a regression covering discount application, shipping selection, removal, and restored order total.

**Checks this iteration:** discount and checkout flow Pest tests passed (10 tests, 53 assertions).

### 2026-09-23 — Acceptance review fixes and remaining admin detail route

- Made the resolved store persist across Livewire update requests. The browser review had exposed a `current_store` container error on storefront cart actions; the add-to-cart drawer and analytics requests now respond successfully.
- Applied additive forward migrations for store invitations, nested navigation, tax snapshots, queued analytics exports, theme file metadata, and canonical analytics fields without resetting the local demo database.
- Fixed a checkout view variable shadowing bug that kept customers on contact after the component advanced, then corrected order and checkout address accessors so saved addresses display in checkout, confirmation, and account order views.
- Added created-at datetime casts for payments and refunds so SQLite timestamps render in admin order details. Added an installed app detail route and page for scopes, store-scoped webhook subscriptions, install metadata, and usage, linked from the apps list.
- Fixed the demo seeder to use the renamed `stop_words_json` schema column; a clean migration plus demo seed now succeeds on an isolated SQLite file.
- Repaired three feature test files that lacked PHP opening tags, fixed the audit formatter logger type, and kept the test assertions aligned with structured audit log output.
- Added checkout progression/address, timestamp display, and app-detail tenant-isolation regressions.
- Independent browser review completed a customer add-to-cart and paid mock checkout, and exposed/followed up on checkout address, confirmation address, and admin payment timestamp display issues. Retesting those fixes and the rest of the customer/admin acceptance checklist is in progress.

**Checks this iteration:** focused checkout/order/apps Pest tests passed (17 tests, 101 assertions); full Pest passed (215 tests, 1,424 assertions); clean isolated SQLite migration plus demo seed passed; `npm run build` passed; `php artisan route:list --except-vendor` shows 120 routes including app details; `vendor/bin/pint --dirty --format agent` and `git diff --check` passed. Final browser review remains in progress.

### 2026-09-23 — Customer session rehydration across requests

- Independent browser review found customer login redirected to `/account`, but the following request redirected back to `/login`.
- Route middleware ordering runs authentication before `store.resolve`. The tenant-scoped customer provider now resolves the storefront tenant from the request hostname when the container store has not been bound yet, then applies the same store ID constraint when restoring a session user.
- Added a regression that logs in through Livewire, clears the current store and resolved auth guards, and requests `/account` in a new HTTP request.
- Browser review had already verified product add-to-cart, a guest checkout/payment/confirmation path, admin order detail, navigation, themes, tax settings, analytics, and developers. Root's fresh-context Playwright MCP retest confirmed customer login remains authenticated after the redirect and a hard refresh. App detail data is covered by Pest because the demo seed does not include installed apps.

**Checks this iteration:** `CustomerAccountTest` passed (8 tests, 43 assertions); full Pest passed (217 tests, 1,433 assertions); Pint, frontend build, and view cache checks passed.

### 2026-09-23 — Final browser acceptance fixes

- Replaced the static storefront cart badge with a Livewire counter that listens for `cart-updated`, so both its visible number and accessible label track cart mutations and reset after checkout.
- Added regression coverage for the tenant-host fallback during customer session restoration and for live cart-count updates.
- Completed a Playwright MCP customer journey: sign in and refresh account page, add two product units, verify drawer and header count, proceed through address/shipping/payment, complete mock card payment, and inspect the resulting order in admin. The confirmed order showed the entered shipping address, masked card, payment timestamp, and correct shipping/tax/total; checkout reset the cart count to zero.
- The browser agent independently reran the full Pest suite and reviewed the customer-session/cart-count/address fixes. Its Playwright MCP transport remained closed, so its browser verification is the root-run evidence recorded above. Full-page app details remain browser-unexercised because the demo seed has no app installations.
- OAuth providers and external PSP integrations remain deferred as described in the roadmap; the implemented checkout uses the specified mock PSP.

**Checks this iteration:** full Pest passed (217 tests, 1,433 assertions); `vendor/bin/pint --dirty --format agent`, `npm run build`, `php artisan view:cache`, isolated SQLite migration plus demo seed, and `git diff --check` passed. The integrated-tree second-agent acceptance review is in progress.

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
| 2026-09-23 | Admin product, discount, inventory, and search screen Pest tests | Passed: 20 tests, 127 assertions |
| 2026-09-23 | `npm run build` and `php artisan view:cache` after admin screen changes | Passed |
| 2026-09-23 | Final current Pest suite after browser-discovered fixes | Passed: 215 tests, 1,424 assertions |
| 2026-09-23 | `npm run build` after final storefront/admin updates | Passed |
| 2026-09-23 | `php artisan route:list --except-vendor` after app detail route | Passed: 120 routes listed |
| 2026-09-23 | `vendor/bin/pint --dirty --format agent` and `git diff --check` | Passed |
| 2026-09-23 | Fresh isolated SQLite migration + demo seed after seeder schema fix | Passed: all migrations and `DemoShopSeeder` completed |
| 2026-09-23 | Customer session rehydration regression | Passed: `CustomerAccountTest` (8 tests, 43 assertions); fresh Playwright MCP login/refresh passed |
| 2026-09-23 | Final full Pest suite and second-agent rerun | Passed: 217 tests, 1,433 assertions |
| 2026-09-23 | Playwright MCP customer-to-paid-order and admin detail journey | Passed: refreshed customer session, live cart count/drawer, address/shipping/tax/payment, confirmation, and admin payment/address detail verified |
| 2026-09-23 | Final Pint, frontend build, cached view compilation, isolated migration/seed, and diff check | Passed |

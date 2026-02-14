# Shop Progress

Last updated: 2026-02-14

## Iteration 1 - Foundation (Completed)

| Checkpoint | Status | Notes |
|---|---|---|
| Tooling baseline (`phpstan.neon`, `deptrac.yaml`, `pint.json`) | Completed | PHPStan and Deptrac configs added, Pint excludes `var/` cache output. |
| Core schema (Phase 1-5,7,8,10 migrations) | Completed | Full multi-tenant commerce schema created for foundation, catalog, checkout, orders, analytics, and apps/webhooks. |
| Enums | Completed | Domain enum set added under `app/Enums/*` for statuses, policies, and types. |
| Models + relationships | Completed | Store, catalog, checkout, order, analytics, and app models scaffolded with casts/relations. |
| Tenant middleware (`ResolveStore`) | Completed | Hostname -> store resolution, container binding, unknown host `404`, suspended store `503`. |
| Role middleware (`CheckStoreRole`) | Completed | Store membership + role gate enforced via `store_users`. |
| Store scoping primitives (`StoreScope`, `BelongsToStore`) | Completed | Global scope and automatic `store_id` assignment implemented. |
| Customer auth provider + guard wiring | Completed | Custom `customer` provider/guard/password broker integrated. |
| Foundation seeders/factories | Completed | Idempotent baseline seed data for org/store/domain/users and core commerce placeholders. |
| Foundation tests | Completed | Tenancy, store-scoping, admin/customer auth, and existing starter auth/settings tests all pass. |

## Iteration 1 Verification

- [x] `composer test`
- [x] `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- [x] `./vendor/bin/deptrac analyse --config-file=deptrac.yaml`

## Next Iteration

- Iteration 2: catalog services + storefront/admin APIs + cart/checkout core workflows + corresponding unit/feature tests.

## Iteration 2 - Catalog, API, and Checkout Workflow (Completed)

| Checkpoint | Status | Notes |
|---|---|---|
| Service layer (`CartService`, `CheckoutService`, `PricingEngine`, `DiscountService`, `ShippingCalculator`, `TaxCalculator`, `InventoryService`) | Completed | Deterministic cart/checkout totals, discount application, shipping quotes, and stock reservation/commit primitives implemented. |
| Domain exceptions + value objects | Completed | Added typed domain exceptions and immutable value objects for pricing/tax/shipping outcomes. |
| Storefront API (`/api/storefront/v1`) | Completed | Cart, checkout, order lookup, search, and analytics ingestion endpoints are implemented and covered by feature tests. |
| Admin API (`/api/admin/v1/stores/{store}/...`) | Completed | Product, collection, and discount CRUD endpoints implemented with tenant-scoped route context. |
| Request validation + JSON resources | Completed | Added dedicated FormRequest classes and admin API resources for consistent payload shape and validation behavior. |
| API and service unit/feature tests | Completed | Added service unit tests and storefront/admin API feature tests for success and failure flows. |
| Static analysis + architecture gates | Completed | Deptrac layer violations resolved; PHPStan config updated and Larastan integrated for Laravel-aware analysis baseline. |

## Iteration 2 Verification

- [x] `composer test`
- [x] `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- [x] `./vendor/bin/deptrac analyse --config-file=deptrac.yaml`

## Next Iteration

- Iteration 3: replace placeholder web routes with implemented storefront/admin pages (server-rendered), wire customer account pages, and expand functional coverage.

## Iteration 3 - Web Flows, Admin CRUD, and Account Enhancements (Completed)

| Checkpoint | Status | Notes |
|---|---|---|
| Storefront web route wiring + controller-backed pages | Completed | Replaced placeholder route layer with controller-driven storefront and account routes. |
| Storefront cart + checkout interactive actions | Completed | Implemented add/update/remove cart lines, start checkout, address/shipping/payment selection, discount apply/remove, and checkout payment completion with order creation. |
| Admin web CRUD forms (products, collections, discounts, pages) | Completed | Replaced placeholder form scaffolds with production form submissions, tenant-scoped validation, and create/update/delete/archival handlers. |
| Account address CRUD | Completed | Added customer address create/update/delete flows and default-address behavior enforcement. |
| Customer auth hardening for remember/reset flows | Completed | Added customer `remember_token` migration and store-scoped customer password reset token repository/manager integration. |
| Web feature coverage expansion | Completed | Added/expanded feature tests for storefront interaction flows and admin CRUD success/failure paths. |
| Tooling gates for this iteration | Completed | Pint, Pest, PHPStan max-level, and Deptrac all green after implementation. |

## Iteration 3 Verification

- [x] `composer test`
- [x] `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- [x] `./vendor/bin/deptrac analyse --config-file=deptrac.yaml`

## Next Iteration

- Iteration 4: independent senior review cycle and Playwright-based acceptance walkthrough (storefront + admin) with bug-fix loop.

## Iteration 4 - Senior Review Hardening and Reliability Fixes (Completed)

| Checkpoint | Status | Notes |
|---|---|---|
| Order-to-checkout linkage for deterministic confirmation/idempotency | Completed | Added `orders.checkout_id` relation + unique constraint and updated completion flows to persist/query by checkout id first. |
| API payment idempotency hardening | Completed | Added row locks + existing-order return path in API pay flow to prevent duplicate orders on repeated requests. |
| Discount finalization correctness | Completed | Checkout completion now increments `discounts.usage_count` once per created order and writes `order_lines.discount_allocations_json` with `{discount_id, code, amount}` entries. |
| Scoped discount allocation correctness | Completed | Replaced naive all-line allocation with line allocations derived from validated pricing/discount engine outputs (`cart_lines.line_discount_amount`), including scoped product rules. |
| Invalidated discount recovery at payment time | Completed | Pay flows now gracefully recover when a previously-applied discount becomes invalid before capture by clearing code and finalizing without discount instead of failing. |
| Inventory lifecycle completion | Completed | Added commit-on-order-completion and release-on-expiry for reserved stock; added scheduled expiration job for abandoned checkouts (`ExpireAbandonedCheckouts`) every 15 minutes. |
| Review-loop regression tests | Completed | Added API/storefront regressions for usage_count, allocation structure/sums, scoped eligibility, invalidated-discount payment recovery, idempotency, inventory commit, and expiry release; added job/schedule tests. |
| Fresh independent reviewer loop | Completed | Multiple fresh-agent review passes executed; all critical/high findings fixed; final pass reports no critical/high blockers. |

## Iteration 4 Verification

- [x] `composer test`
- [x] `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- [x] `./vendor/bin/deptrac analyse --config-file=deptrac.yaml`

## Next Iteration

- Iteration 5: Playwright end-to-end acceptance walkthrough (customer + admin) against `http://shop.test/`, with fix-and-retest loop for any discovered defects.

## Iteration 5 - Playwright Acceptance and Runtime Defect Fixes (Completed)

| Checkpoint | Status | Notes |
|---|---|---|
| Customer account registration redirect-loop fix | Completed | Enforced middleware priority so `ResolveStore` runs before auth checks (`bootstrap/app.php`), preventing `/account` ↔ `/account/login` redirect loops on tenant hosts. |
| Admin document title rendering fix | Completed | Corrected Blade title rendering to evaluate store suffix instead of outputting literal concatenation text (`resources/views/admin/layout.blade.php`). |
| Storefront order detail route fix for `#` prefixed order numbers | Completed | Fixed account order links to use path-safe order numbers and centralized normalization in `Order` model for route input matching. |
| Regression test hardening for discovered defects | Completed | Added storefront tests for redirect-loop, order-link normalization (including `%23` encoded route), order empty state, and cross-customer order access denial; added admin title-rendering regression test. |
| Full Playwright acceptance walkthrough (storefront + admin) | Completed | Restarted and completed browser review after fixes: customer checkout/payment/account/orders/addresses/logout and admin login/dashboard/orders/products/discounts/pages/logout all verified on `http://shop.test/`. |
| Fresh independent reviewer loop | Completed | Ran fresh-agent review passes; addressed maintainability and missing-edge test feedback; final reviewed state has no critical/high findings. |
| Full quality gate rerun (`composer test`, PHPStan, Deptrac, Pint`) | Completed | Re-ran full suite and static/architecture/style checks on final code. |

## Iteration 5 Verification

- [x] `composer test`
- [x] `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- [x] `./vendor/bin/deptrac analyse --config-file=deptrac.yaml`
- [x] `./vendor/bin/pint --dirty --test`
- [x] Playwright MCP acceptance walkthrough on `http://shop.test/` (customer + admin, post-fix restart completed)

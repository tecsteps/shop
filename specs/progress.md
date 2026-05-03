# Shop Build Progress

## Objective

Build a complete, self-contained Laravel shop system from `specs/*`, with implemented acceptance criteria mapped to evidence, passing automated checks, verified browser flows, independent QA, and meaningful commits.

## Current Status

- Status: in progress
- Active slice: Phase 2 - Catalog data layer, seed graph, and media processing
- Started from: Laravel Livewire/Fortify starter kit with no shop domain tables or models present
- Last updated: 2026-05-03

## Phased Plan

1. Foundation: SQLite config, tenancy tables/models/factories/seeders, store resolution middleware, store scoping, admin/customer auth foundations, policies, and Phase 1 tests.
2. Catalog: product, variant, inventory, collection, media schema/models/services, admin catalog CRUD, storefront browsing, and tests.
3. Storefront theme/layout: theme/page/navigation data, storefront layout, product/collection/search/page views, and smoke browser verification.
4. Cart/checkout/pricing: cart API/UI, discount, shipping, tax, checkout state machine, and tests.
5. Payments/orders/fulfillment: mock PSP, order creation, refunds, fulfillments, customer account order views, and tests.
6. Admin panel: dashboard and all admin list/form/detail pages from Spec 03, with Livewire/Flux interactions.
7. Search/analytics/apps/webhooks: SQLite search, event aggregation, developer token/webhook UI, delivery jobs, and tests.
8. Final verification: full Pest suite, Pint, frontend build, Playwright MCP customer/admin flows, independent QA, fixes, and completion audit.

## Acceptance Checklist

| Area | Criteria Source | Status | Evidence |
| --- | --- | --- | --- |
| Database schema | `specs/01-DATABASE-SCHEMA.md` | partial | Phase 1 tenancy/auth tables plus Phase 2 catalog tables implemented: products, product_options, product_option_values, product_variants, variant_option_values, inventory_items, collections, collection_products, product_media. Boost schema confirmed these tables after `migrate:fresh --seed`. |
| Routes/API | `specs/02-API-ROUTES.md` | partial | Phase 1 routes exist: `/`, `/admin`, `/admin/login`, `/admin/logout`, `/account/login`, `/account/register`, `/account`. API routes are still missing. |
| Admin UI | `specs/03-ADMIN-UI.md` | missing | Starter dashboard/settings only. |
| Storefront UI | `specs/04-STOREFRONT-UI.md` | partial | Storefront home still starter welcome; customer login/register/account placeholders exist. |
| Business logic | `specs/05-BUSINESS-LOGIC.md` | partial | `ResolveStore`, `BelongsToStore`, `StoreScope`, role checks, customer guard provider, catalog product lifecycle transitions, SKU uniqueness, variant matrix rebuilding, inventory reserve/release/commit/restock, automatic variant inventory, variant option mapping, and media resize/cleanup job implemented. Cart/checkout/order services still missing. |
| Auth/security | `specs/06-AUTH-AND-SECURITY.md` | partial | Admin Livewire login/logout, customer guard/provider, customer login/register, login rate limiting, session hardening, and resource policies implemented. Customer password reset and Sanctum token auth still missing. |
| Seed/test data | `specs/07-SEEDERS-AND-TEST-DATA.md` | partial | Seeders create Acme Fashion and Acme Electronics stores/domains/settings/admin access, 6 collections, 25 products, 127 variants, 127 inventory rows, 206 variant option pivots, and no product media. Order/discount/theme/content seed data still missing. |
| Browser E2E plan | `specs/08-PLAYWRIGHT-E2E-PLAN.md` | missing | No browser tests yet. |
| Roadmap phases | `specs/09-IMPLEMENTATION-ROADMAP.md` | in progress | Phase 1 foundation committed. Phase 2 catalog data layer, business services, media job, and seed graph are implemented; admin catalog CRUD and storefront catalog browsing are still pending. |

## Verification Evidence

- 2026-05-03: `mcp__laravel_boost__.application_info` confirmed PHP 8.4, Laravel 12.51.0, Livewire 4.1.4, Flux 2.12.0, Pest 4.3.2, SQLite.
- 2026-05-03: `mcp__laravel_boost__.database_schema(summary: true)` returned no tables for the current configured database.
- 2026-05-03: `git status --short` showed no current worktree changes before implementation.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Laravel 12 middleware/auth/authorization/migration docs plus Livewire 4 and Pest 4 docs before code changes.
- 2026-05-03: Planning challenger sub-agent found the repo was still a starter kit and produced the acceptance checklist by vertical slice. Risks added here: missing Sanctum dependency, password_hash/Fortify coordination, route/API absence, and E2E count inconsistencies.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed on real `database/database.sqlite` after creating the missing SQLite file.
- 2026-05-03: `php artisan test --compact` passed: 45 tests, 121 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after formatting changes.
- 2026-05-03: `php artisan route:list --path=admin` showed `/admin`, `/admin/login`, `/admin/logout`; `php artisan route:list --path=account` showed `/account`, `/account/login`, `/account/register`.
- 2026-05-03: Boost schema summary confirmed Phase 1 tables: organizations, stores, store_domains, store_users, store_settings, customers, customer_password_reset_tokens, users with password_hash.
- 2026-05-03: Playwright MCP verified `http://shop.test/` loads, `http://shop.test/admin/login` renders, admin login with `admin@acme.test` / `password` reaches `/admin`, `http://shop.test/account/login` renders with no console warnings/errors after the Livewire layout fix, and customer login with `customer@acme.test` / `password` reaches `/account`.
- 2026-05-03: Independent Phase 2 QA agents reported no critical findings. High findings on lifecycle bypass, child tenant scoping, inventory store mismatch, variant option mapping, simplified seed data, and media status-only processing were addressed in the catalog data layer.
- 2026-05-03: `php artisan test --compact tests/Feature/Catalog` passed: 19 tests, 71 assertions.
- 2026-05-03: `php artisan test --compact` passed: 64 tests, 192 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` fixed import/order/spacing, followed by passing catalog and full Pest suites.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after the Phase 2 seed update.
- 2026-05-03: Boost schema summary confirmed Phase 2 catalog tables. Boost query counts after fresh seed: stores 2, products 25, variants 127, inventory_items 127, collections 6, product_media 0, variant_option_values 206.

## Decisions

- Follow the roadmap order strictly. Phase 1 is required before catalog, checkout, admin, storefront, and QA flows can be meaningfully implemented.
- Preserve the starter Fortify conventions where they do not conflict with the shop specs.
- Use integer minor units for all money columns and JSON text columns cast through Eloquent as specified.
- Keep `password_hash` as the database column for admin users and customers while exposing `password`/`getAuthPassword()` at the model layer for Fortify/Laravel auth compatibility.
- Seed both `shop.test` and `acme-fashion.test` as storefront domains for the first store so the goal URL and E2E spec URL can both resolve.
- Customer Livewire auth components persist `storeId` from the initial storefront request because Livewire update requests do not run through the original storefront route middleware.
- The seed specification contains an arithmetic conflict: Acme Fashion's per-product variant table sums to 117 variants, while the prose says 107. The implementation follows the concrete per-product table, resulting in 117 Fashion variants and 10 Electronics variants, 127 total.
- Child catalog models without a `store_id` column are tenant-scoped through their parent product relationship. Inventory keeps its denormalized `store_id` and enforces that it matches the variant product store at save time.

## Open Issues

- Composer does not currently include Sanctum, while Spec 06 requires Sanctum personal access tokens. This will need either an approved dependency addition or a documented compatible alternative before final completion.
- The Herd app URL in the goal is `http://shop.test/`, while the E2E spec also references `http://acme-fashion.test`; domain handling must support seeded store domains and Herd verification.
- Phase 1 still lacks admin/customer password reset at the spec paths and a custom customer password reset token repository that scopes by store_id.
- Policy classes for later resources use generic object parameters until the concrete catalog/order/content models exist.
- `php artisan route:list --except-vendor` hides Livewire full-page routes because their controller is vendor-provided; path-filtered route-list commands are used as evidence for those routes.
- Phase 2 catalog UI is still missing: no admin product/collection CRUD routes or storefront product/collection browsing routes have been implemented yet.
- SQLite enum/check constraints from the schema spec are not yet explicitly enforced as database `CHECK` constraints; enum validation is currently enforced through casts/services/model invariants.
- Product media processing now validates and resizes image uploads with GD and cleans predictable generated paths, but browser/admin upload UI is still pending.

## Completion Summary

Not complete. Phase 1 foundation is committed and the Phase 2 catalog data layer is implemented enough to support admin/storefront catalog UI work, with known auth/token, UI route, API, and database CHECK constraint gaps tracked above.

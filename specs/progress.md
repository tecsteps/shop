# Shop Build Progress

## Objective

Build a complete, self-contained Laravel shop system from `specs/*`, with implemented acceptance criteria mapped to evidence, passing automated checks, verified browser flows, independent QA, and meaningful commits.

## Current Status

- Status: in progress
- Active slice: Phase 4 - Cart, checkout, pricing, discounts, shipping, and tax foundation
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
| Database schema | `specs/01-DATABASE-SCHEMA.md` | partial | Phase 1 tenancy/auth tables, Phase 2 catalog tables, and Phase 3 theme/content/navigation tables are implemented: themes, theme_files, theme_settings, pages, navigation_menus, navigation_items. Cart/checkout/order/search/analytics/app tables are still missing. |
| Routes/API | `specs/02-API-ROUTES.md` | partial | Phase 1 auth routes plus catalog UI routes exist: `/`, `/collections`, `/collections/{handle}`, `/products/{handle}`, `/search`, `/pages/{handle}`, `/admin/products`, `/admin/products/create`, `/admin/products/{product}/edit`, `/admin/collections`, `/admin/collections/create`, `/admin/collections/{collection}/edit`, `/admin/inventory`. API routes are still missing. |
| Admin UI | `specs/03-ADMIN-UI.md` | partial | Flux/Livewire admin catalog shell now includes product index/form, collection index/form, and inventory list with auth protection, filtering, pagination, status changes, SKU uniqueness checks, and collection assignment. Order/customer/content/settings admin pages are still missing. |
| Storefront UI | `specs/04-STOREFRONT-UI.md` | partial | Storefront layout, home, collections index/detail, product detail, search, breadcrumbs, price display, product cards, DB-backed pages, seeded navigation menus, and cached theme settings render seeded catalog/content data. Cart interaction is currently a UI dispatch only. |
| Business logic | `specs/05-BUSINESS-LOGIC.md` | partial | `ResolveStore`, `BelongsToStore`, `StoreScope`, role checks, customer guard provider, catalog product lifecycle transitions, SKU uniqueness, variant matrix rebuilding, inventory reserve/release/commit/restock, automatic variant inventory, variant option mapping, and media resize/cleanup job implemented. Cart/checkout/order services still missing. |
| Auth/security | `specs/06-AUTH-AND-SECURITY.md` | partial | Admin Livewire login/logout, customer guard/provider, customer login/register, login rate limiting, session hardening, and resource policies implemented. Customer password reset and Sanctum token auth still missing. |
| Seed/test data | `specs/07-SEEDERS-AND-TEST-DATA.md` | partial | Seeders create Acme Fashion and Acme Electronics stores/domains/settings/admin access, 6 collections, 25 products, 127 variants, 127 inventory rows, 206 variant option pivots, 2 themes, 6 theme files, 2 theme settings rows, 6 pages, 4 navigation menus, 17 navigation items, and no product media. Order/discount/shipping/tax seed data still missing. |
| Browser E2E plan | `specs/08-PLAYWRIGHT-E2E-PLAN.md` | partial | Manual Playwright MCP smoke coverage has verified storefront catalog browsing, product detail, search, DB-backed content pages, seeded navigation, admin product/collection/inventory pages, and auth flows without console warnings/errors on successful pages. Automated browser tests are still missing. |
| Roadmap phases | `specs/09-IMPLEMENTATION-ROADMAP.md` | in progress | Phase 1 foundation, Phase 2 catalog data/UI, and Phase 3 theme/content/navigation data with storefront consumption are implemented. Phase 4 cart/checkout/pricing is next. |

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
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Livewire 4 full-page component routing/testing docs and Flux UI form/input/select/checkbox/button docs before catalog UI changes.
- 2026-05-03: `php artisan route:list --path=admin` showed 10 admin routes including product, collection, and inventory catalog pages. `php artisan route:list --path=collections`, `--path=products`, `--path=search`, and `--path=pages` showed storefront catalog/content routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Catalog/CatalogUiTest.php` passed: 4 tests, 44 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Catalog` passed: 23 tests, 115 assertions.
- 2026-05-03: `php artisan test --compact` passed: 68 tests, 236 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after formatting catalog UI changes.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after catalog UI test changes.
- 2026-05-03: `npm run build` passed with Vite production assets generated under ignored `public/build`.
- 2026-05-03: Playwright MCP verified `http://shop.test/`, `/collections/t-shirts`, `/products/classic-cotton-t-shirt`, `/search?q=Cotton`, `/pages/about`, `/admin/products`, `/admin/products/create`, `/admin/collections`, and `/admin/inventory` render with no console warnings/errors.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Laravel 12 migration/model/factory/seeder/cache docs and Pest 4 database testing docs before Phase 3 data-layer changes.
- 2026-05-03: Phase 3 explorer QA confirmed the required theme/page/navigation migrations, models, enums, factories, seeders, services, and tests; noted the spec tree-navigation mismatch because `navigation_items` has no `parent_id`.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after adding Phase 3 migrations and seeders.
- 2026-05-03: Boost schema summaries confirmed Phase 3 tables: themes, theme_files, theme_settings, pages, navigation_menus, navigation_items. Boost query counts after fresh seed: themes 2, theme_files 6, theme_settings 2, pages 6, navigation_menus 4, navigation_items 17.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/ThemeDataTest.php` passed: 6 tests, 31 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront tests/Feature/Catalog` passed: 29 tests, 146 assertions.
- 2026-05-03: `php artisan test --compact` passed: 74 tests, 267 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Phase 3 changes.
- 2026-05-03: `npm run build` passed after Phase 3 layout changes.
- 2026-05-03: Playwright MCP verified `http://shop.test/` renders seeded main navigation and `http://shop.test/pages/faq` renders DB-backed page content with no console warnings/errors.

## Decisions

- Follow the roadmap order strictly. Phase 1 is required before catalog, checkout, admin, storefront, and QA flows can be meaningfully implemented.
- Preserve the starter Fortify conventions where they do not conflict with the shop specs.
- Use integer minor units for all money columns and JSON text columns cast through Eloquent as specified.
- Keep `password_hash` as the database column for admin users and customers while exposing `password`/`getAuthPassword()` at the model layer for Fortify/Laravel auth compatibility.
- Seed both `shop.test` and `acme-fashion.test` as storefront domains for the first store so the goal URL and E2E spec URL can both resolve.
- Customer Livewire auth components persist `storeId` from the initial storefront request because Livewire update requests do not run through the original storefront route middleware.
- The seed specification contains an arithmetic conflict: Acme Fashion's per-product variant table sums to 117 variants, while the prose says 107. The implementation follows the concrete per-product table, resulting in 117 Fashion variants and 10 Electronics variants, 127 total.
- Child catalog models without a `store_id` column are tenant-scoped through their parent product relationship. Inventory keeps its denormalized `store_id` and enforces that it matches the variant product store at save time.
- Storefront content pages now resolve from the `pages` table and only published pages are rendered.
- The catalog admin form intentionally blocks active products without a priced variant and duplicate in-store SKUs during UI edits, mirroring service-layer invariants where the current CRUD surface touches product variants directly.
- Navigation tree support is flat for now because the Phase 3 schema defines `navigation_items.position` but no `parent_id`; `NavigationService::buildTree()` returns a flat tree-compatible array with empty children.

## Open Issues

- Composer does not currently include Sanctum, while Spec 06 requires Sanctum personal access tokens. This will need either an approved dependency addition or a documented compatible alternative before final completion.
- The Herd app URL in the goal is `http://shop.test/`, while the E2E spec also references `http://acme-fashion.test`; domain handling must support seeded store domains and Herd verification.
- Phase 1 still lacks admin/customer password reset at the spec paths and a custom customer password reset token repository that scopes by store_id.
- Policy classes for later resources use generic object parameters until the concrete catalog/order/content models exist.
- `php artisan route:list --except-vendor` hides Livewire full-page routes because their controller is vendor-provided; path-filtered route-list commands are used as evidence for those routes.
- Phase 2 media upload/admin UI is still missing, even though the media schema/job layer exists.
- Product options can be displayed and edited as text in the admin form, but full option matrix generation and option-value reassignment UI are not complete.
- Theme/page/navigation admin management UI is still missing even though the Phase 3 data layer, seeders, services, and storefront consumption exist.
- Storefront product detail "Add to cart" currently dispatches a browser event only; cart persistence and checkout are still pending later roadmap phases.
- Automated browser tests from Spec 08 are still missing; current browser coverage is manual Playwright MCP smoke verification.
- SQLite enum/check constraints from the schema spec are not yet explicitly enforced as database `CHECK` constraints; enum validation is currently enforced through casts/services/model invariants.
- Product media processing now validates and resizes image uploads with GD and cleans predictable generated paths, but browser/admin upload UI is still pending.

## Completion Summary

Not complete. Phase 1 foundation, Phase 2 catalog data/UI surfaces, and Phase 3 storefront theme/content/navigation data are implemented enough to support cart and checkout work, with known auth/token, media UI, theme admin UI, API, browser-test, and database CHECK constraint gaps tracked above.

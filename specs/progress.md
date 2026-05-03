# Shop Build Progress

## Objective

Build a complete, self-contained Laravel shop system from `specs/*`, with implemented acceptance criteria mapped to evidence, passing automated checks, verified browser flows, independent QA, and meaningful commits.

## Current Status

- Status: in progress
- Active slice: Phase 1 - Foundation, tenancy, core auth, authorization
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
| Database schema | `specs/01-DATABASE-SCHEMA.md` | partial | Phase 1 tenancy/auth tables implemented: organizations, stores, store_domains, store_users, store_settings, customers, customer_password_reset_tokens, password_hash users. Boost schema confirmed these tables after `migrate:fresh --seed`. |
| Routes/API | `specs/02-API-ROUTES.md` | partial | Phase 1 routes exist: `/`, `/admin`, `/admin/login`, `/admin/logout`, `/account/login`, `/account/register`, `/account`. API routes are still missing. |
| Admin UI | `specs/03-ADMIN-UI.md` | missing | Starter dashboard/settings only. |
| Storefront UI | `specs/04-STOREFRONT-UI.md` | partial | Storefront home still starter welcome; customer login/register/account placeholders exist. |
| Business logic | `specs/05-BUSINESS-LOGIC.md` | partial | `ResolveStore`, `BelongsToStore`, `StoreScope`, role checks, and customer guard provider implemented. Catalog/cart/checkout/order services still missing. |
| Auth/security | `specs/06-AUTH-AND-SECURITY.md` | partial | Admin Livewire login/logout, customer guard/provider, customer login/register, login rate limiting, session hardening, and resource policies implemented. Customer password reset and Sanctum token auth still missing. |
| Seed/test data | `specs/07-SEEDERS-AND-TEST-DATA.md` | partial | Phase 1 seeders create Acme organization/store/domains, owner admin, store settings, and test customer. Full catalog/order/discount/theme seed data still missing. |
| Browser E2E plan | `specs/08-PLAYWRIGHT-E2E-PLAN.md` | missing | No browser tests yet. |
| Roadmap phases | `specs/09-IMPLEMENTATION-ROADMAP.md` | in progress | Phase 1 foundation substantially implemented; next blocking slice is catalog schema/models/services. |

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

## Decisions

- Follow the roadmap order strictly. Phase 1 is required before catalog, checkout, admin, storefront, and QA flows can be meaningfully implemented.
- Preserve the starter Fortify conventions where they do not conflict with the shop specs.
- Use integer minor units for all money columns and JSON text columns cast through Eloquent as specified.
- Keep `password_hash` as the database column for admin users and customers while exposing `password`/`getAuthPassword()` at the model layer for Fortify/Laravel auth compatibility.
- Seed both `shop.test` and `acme-fashion.test` as storefront domains for the first store so the goal URL and E2E spec URL can both resolve.
- Customer Livewire auth components persist `storeId` from the initial storefront request because Livewire update requests do not run through the original storefront route middleware.

## Open Issues

- Composer does not currently include Sanctum, while Spec 06 requires Sanctum personal access tokens. This will need either an approved dependency addition or a documented compatible alternative before final completion.
- The Herd app URL in the goal is `http://shop.test/`, while the E2E spec also references `http://acme-fashion.test`; domain handling must support seeded store domains and Herd verification.
- No critical/high QA can be claimed until independent QA agents inspect the implemented slices.
- Phase 1 still lacks admin/customer password reset at the spec paths and a custom customer password reset token repository that scopes by store_id.
- Policy classes for later resources use generic object parameters until the concrete catalog/order/content models exist.
- `php artisan route:list --except-vendor` hides Livewire full-page routes because their controller is vendor-provided; path-filtered route-list commands are used as evidence for those routes.

## Completion Summary

Not complete. Phase 1 foundation is implemented enough to support the next catalog slice, with known auth/token gaps tracked above.

# Implementation Progress

> Live tracking of the shop system build. Updated continuously.

## Status Overview

| Phase | Name | Status |
|-------|------|--------|
| P1 | Foundation (migrations, models, enums, middleware, auth) | ✅ Done |
| P2 | Catalog (products, variants, inventory, collections, media) | ✅ Done |
| P3 | Themes, Pages, Navigation, Storefront Layout | ✅ Done |
| P4 | Cart, Checkout, Discounts, Shipping, Taxes | ✅ Done |
| P5 | Payments, Orders, Fulfillment | ✅ Done |
| P6 | Customer Accounts | ✅ Done |
| P7 | Admin Panel | ✅ Done |
| P8 | Search (FTS5) | ✅ Done |
| P9 | Analytics | ✅ Done |
| P10 | Apps and Webhooks | ✅ Done |
| P11 | Polish (a11y, dark mode, error pages, seeders) | ✅ Done |
| P12 | Full Test Suite + Playwright E2E | ✅ Done (178 Pest tests + Playwright E2E) |

## Test Status

- **178 Pest tests passing** (269 assertions), 0 failures, 0 risky.
- Unit: PricingEngine, DiscountCalculator, TaxCalculator, ShippingCalculator, CartVersion, HandleGenerator.
- Feature: Tenancy, Auth (admin/customer/Sanctum), Cart, Products, Checkout, Orders, Payments, Customers, Admin, Search, Analytics, Webhooks, API.

## Playwright E2E (simulated user behaviour)

Verified via Playwright (Chromium) against `http://shop.test`:
- Storefront: home, product, collection, cart render; variant selection + add-to-cart works.
- Checkout page renders and progresses contact → address → shipping steps.
- Admin: login (admin@acme.test / password), dashboard, products, orders render.
- **4 real bugs found and fixed**:
  1. Cart drawer + search modal overlays (`fixed inset-0`) intercepted all clicks → added `pointer-events-none`/`pointer-events-auto`.
  2. `optionAvailability` computed included the currently-selected value for the same option → variants appeared unavailable.
  3. `ResolveStore` did not run for Livewire update requests → `current_store` unbound (500 on add-to-cart) → added `store.resolve` to the web group + auth/session-based admin detection.
  4. `storefront-order-summary` missing `$discountError`/`$discountCode` props.
- JSON-LD `@context` Blade directive collision fixed (moved to `@php` blocks).

## Backend Completed

- 55-table schema (SQLite WAL + FKs), 44 models + StoreScope/BelongsToStore, 27 enums.
- Services: Inventory, Product, VariantMatrix, Cart, Discount, Tax, Shipping, PricingEngine, Checkout, Payment (mock PSP), Order, Refund, Fulfillment, Customer, Search (FTS5), Analytics, Webhook, Navigation.
- Middleware: ResolveStore, CheckStoreRole, CustomerAuthenticate; 11 policies; 7 rate limiters.
- Auth: admin (web) + customer (store-scoped guard) + Sanctum tokens.
- Admin panel (30 Livewire components) + storefront (17 Livewire components) with Flux UI + Tailwind v4 + dark mode.
- 18 seeders (demo stores; `php artisan db:seed` idempotent).

## Notes

- Monetary amounts: integer minor units (cents). Tax uses intdiv (truncation) to match roadmap's concrete expected values (1044, 629).
- `AnalyticsDaily` uses explicit `$table = 'analytics_daily'`.
- Obsolete Livewire starter-kit scaffold tests removed (replaced by spec's suite).
- `shop.test` added as storefront domain for local E2E (seeders use `acme-fashion.test`).

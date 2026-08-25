# Implementation Progress

> Live tracking of the shop system build. Updated continuously.

## Status Overview

| Phase | Name | Status |
|-------|------|--------|
| P1 | Foundation (migrations, models, enums, middleware, auth) | ✅ Done |
| P2 | Catalog (products, variants, inventory, collections, media) | ✅ Done |
| P3 | Themes, Pages, Navigation, Storefront Layout | 🔄 In Progress (UI subagent) |
| P4 | Cart, Checkout, Discounts, Shipping, Taxes | ✅ Done |
| P5 | Payments, Orders, Fulfillment | ✅ Done |
| P6 | Customer Accounts | ✅ Backend done (UI in progress) |
| P7 | Admin Panel | 🔄 In Progress (UI subagent) |
| P8 | Search | ✅ Done |
| P9 | Analytics | ✅ Done |
| P10 | Apps and Webhooks | ✅ Done |
| P11 | Polish (a11y, dark mode, error pages, seeders) | 🔄 Seeders done, polish pending |
| P12 | Full Test Suite + Playwright E2E | ⏳ Pending |

## Test Status

- **148 tests passing** (unit + feature), 232 assertions.
- Unit: PricingEngine, DiscountCalculator, TaxCalculator, ShippingCalculator, CartVersion, HandleGenerator.
- Feature: Tenancy (StoreIsolation), Auth (Admin/Customer), Cart (service+API), Products (CRUD/Variant/Inventory/Collection), Checkout (flow/state), Orders (creation/refund/fulfillment), Payments, Search, Analytics, Webhooks, API (admin product/order + storefront checkout).

## Backend Completed

- 55-table schema (SQLite, WAL, FKs), 44 models + StoreScope/BelongsToStore, 27 enums.
- Services: Inventory, Product, VariantMatrix, Cart, Discount, Tax, Shipping, PricingEngine, Checkout, Payment (mock PSP), Order, Refund, Fulfillment, Customer, Search (FTS5), Analytics, Webhook, Navigation.
- Middleware: ResolveStore, CheckStoreRole, CustomerAuthenticate; 11 policies; rate limiters.
- Auth: admin (web guard) + customer (store-scoped guard) + Sanctum tokens.
- Routes: web (admin/storefront/checkout/account), API (storefront + admin), console schedules.
- 18 seeders (demo store, verified idempotent via `php artisan db:seed`).

## Remaining

- Storefront/Admin Livewire UI components + views (subagents in progress).
- Customer account feature tests + Admin UI feature tests + TenantResolutionTest (need UI).
- SanitizeHtml action, HTML sanitization.
- Playwright E2E (spec 08), 2nd-agent verification, review meeting.

## Notes

- Built from scratch (no reuse of other-branch implementations).
- Monetary amounts: integer minor units (cents). Tax uses intdiv (truncation) to match the roadmap's concrete expected values (1044, 629, etc.).
- `AnalyticsDaily` uses explicit `$table = 'analytics_daily'`.
- Obsolete Livewire starter-kit scaffold tests removed (replaced by spec's suite).

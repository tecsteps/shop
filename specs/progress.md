# Shop Implementation Progress

Branch: `2026-07-18-cursor-grok-4-5`  
Started: 2026-07-18  
Approach: Build from scratch on clean Laravel Livewire starter (no reuse of other branches).

## Status Overview

| Phase | Name | Status | Notes |
|-------|------|--------|-------|
| 1 | Foundation | ✅ done | Migrations, models, middleware, auth, policies |
| 2 | Catalog | ✅ data layer done | Products, variants, inventory, collections, media |
| 3 | Themes & Storefront Layout | 🟡 data layer done | Themes, pages, navigation models; UI pending |
| 4 | Cart, Checkout, Discounts, Shipping, Taxes | 🟡 domain layer done | Data, calculations, checkout state machine; UI pending |
| 5 | Payments, Orders, Fulfillment | 🟡 domain layer done | Mock PSP, orders, refunds, fulfillment; UI pending |
| 6 | Customer Accounts | ⏳ pending | Customer guard + account pages |
| 7 | Admin Panel | ⏳ pending | Livewire admin UI |
| 8 | Search | ⏳ pending | FTS5 + UI |
| 9 | Analytics | ⏳ pending | Events + daily aggregates |
| 10 | Apps and Webhooks | ⏳ pending | Extensibility |
| 11 | Polish | ⏳ pending | A11y, dark mode, seeders |
| 12 | Full Test Suite + Playwright | ⏳ pending | Pest + MCP confirmation |

## Iteration Log

### 2026-07-18 — Kickoff
- Confirmed clean starter (no shop domain code, empty DB).
- Specs loaded; roadmap phases 1–12 identified.
- Progress file created; Phase 1 starting.

### 2026-07-18 — Phase 1 + Catalog data layer
- Migrations for org/store/customers/catalog
- Models, enums, factories, BelongsToStore + StoreScope
- ResolveStore middleware, customer guard config
- Policies, ProductService, InventoryService, VariantMatrixService, HandleGenerator
- Pest: TenantResolution, StoreIsolation, Inventory, HandleGenerator (12 passing)

### 2026-07-18 — Phases 3–5 data and domain layer
- Added themes, pages, navigation, carts, checkout, shipping, tax, discounts, orders, payments, refunds, and fulfillment schema.
- Added models, enums, factories, value objects, payment contract, scheduled jobs, and order lifecycle events.
- Implemented integer-only pricing, tax, shipping, discounts, cart operations, checkout, mock payments, order creation, refunds, and fulfillment.
- Pest: 17 new tests passing with 51 assertions.

### 2026-07-18 — Phase 1/2 foundation verification
- Phase 1 users schema now keeps Laravel/Fortify's `password` hash column, includes status and last-login fields in the base migration, and preserves the existing two-factor migration.
- Added SQLite-backed `CHECK` constraints for all Phase 1/2 enum columns, the customer password-reset token schema, and the required SQLite connection pragmas.
- Completed model defaults, enum/JSON/date casts, tenant relationships, customer auth compatibility, consistent factories, role policies, and dependency-ordered foundation seeders.
- Expanded Pest coverage for hostname/session tenant resolution, cache behavior, store isolation, model/factory graphs, auth configuration, database constraints, and the role matrix.

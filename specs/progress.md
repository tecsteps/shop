# Implementation Progress

Tracking implementation of the shop system per `specs/09-IMPLEMENTATION-ROADMAP.md`.

| Phase | Scope | Status | Tests |
|-------|-------|--------|-------|
| 1 | Foundation: config, tenancy migrations/models, enums, ResolveStore, BelongsToStore, admin+customer auth, policies, Pest helpers | in progress | Tenancy/*, Auth/AdminAuthTest, Auth/CustomerAuthTest (cart merge deferred to P4) |
| 2 | Catalog: products, options, variants, inventory, collections, media | pending | Products/* |
| 3 | Themes, pages, navigation, storefront layout + components | pending | (rendering covered later) |
| 4 | Cart, checkout, discounts, shipping, taxes + storefront cart/checkout UI | pending | Unit/*, Cart/CartServiceTest, Checkout/* |
| 5 | Payments (mock PSP), orders, refunds, fulfillments, events | pending | Orders/*, Payments/* |
| 6 | Customer accounts | pending | Customers/*, finish CustomerAuthTest |
| 7 | Admin panel (all sections) | pending | Admin/* |
| API | Spec 02 REST APIs + Sanctum | pending | Api/*, Auth/SanctumTokenTest, Cart/CartApiTest |
| 8 | Search (FTS5) | pending | Search/* |
| 9 | Analytics | pending | Analytics/* |
| 10 | Apps & webhooks | pending | Webhooks/* |
| 11 | Polish, seeders (spec 07), error pages, dark mode, a11y | pending | - |
| 12 | Full suite + browser tests (spec 08) + Playwright MCP acceptance verification | pending | Browser/* |

## Log

- 2026-06-09: Project start. Fresh Livewire starter kit (Fortify). Created progress tracker, started Phase 1.

# Progress Log

## Iteration 1 - Foundation kickoff
- Status: completed
- Goal: establish multi-tenant core, domain scaffolding, and baseline seed data.
- Workstreams:
  - [x] Core migrations and models
  - [x] Store resolution middleware and tenant context
  - [x] Guards/auth wiring for admin and customer
  - [x] Baseline seeders for demo stores/users/products
  - [x] Service layer scaffold (cart, checkout, pricing, shipping, tax, payment, inventory, order)

## Iteration 2 - UI + routes integration
- Status: completed
- Goal: deliver end-to-end storefront/admin flows and wire API/web routes.
- Workstreams:
  - [x] Storefront pages, cart, checkout, confirmation, customer account
  - [x] Admin dashboard + core management sections
  - [x] Versioned API routes/controllers for storefront/admin
  - [x] Route mounting (`web`, `admin`, `storefront`, `api`)
  - [x] Pest suites aligned to shop specs

## Iteration 3 - Search/analytics/apps + hardening
- Status: in progress
- Goal: complete phases 8-10 and stabilize with full test coverage.
- Workstreams:
  - [x] Search settings, query tracking, and FTS index support
  - [x] Analytics events/daily aggregation + queue job
  - [x] Apps/webhooks/oauth baseline models, services, and jobs
  - [x] Admin sections for pages/navigation/themes/search/analytics/apps/developers
  - [x] Full Pest suite replacement for implemented shop behavior
  - [x] Pest run green (`29 passed`)
  - [ ] Playwright acceptance walkthrough and bug-fix loop on `http://shop.test`

## Phase Tracker (from roadmap)
- [x] Phase 1 Foundation
- [x] Phase 2 Catalog
- [x] Phase 3 Themes/Pages/Navigation/Storefront Layout
- [x] Phase 4 Cart/Checkout/Discounts/Shipping/Taxes
- [x] Phase 5 Payments/Orders/Fulfillment
- [x] Phase 6 Customer Accounts
- [x] Phase 7 Admin Panel
- [x] Phase 8 Search
- [x] Phase 9 Analytics
- [x] Phase 10 Apps/Webhooks
- [ ] Phase 11 Polish
- [ ] Phase 12 Full test + Playwright verification

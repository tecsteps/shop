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
- Status: in progress
- Goal: deliver end-to-end storefront/admin flows and wire API/web routes.
- Workstreams:
  - [x] Storefront pages, cart, checkout, confirmation, customer account
  - [x] Admin dashboard + core management sections
  - [x] Versioned API routes/controllers for storefront/admin
  - [x] Route mounting (`web`, `admin`, `storefront`, `api`)
  - [ ] Pest suites aligned to shop specs
  - [ ] Playwright acceptance walkthrough and bug-fix loop

## Phase Tracker (from roadmap)
- [x] Phase 1 Foundation
- [x] Phase 2 Catalog
- [x] Phase 3 Themes/Pages/Navigation/Storefront Layout
- [x] Phase 4 Cart/Checkout/Discounts/Shipping/Taxes
- [x] Phase 5 Payments/Orders/Fulfillment
- [x] Phase 6 Customer Accounts
- [x] Phase 7 Admin Panel
- [ ] Phase 8 Search
- [ ] Phase 9 Analytics
- [ ] Phase 10 Apps/Webhooks
- [ ] Phase 11 Polish
- [ ] Phase 12 Full test + Playwright verification

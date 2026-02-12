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
- Status: completed
- Goal: complete phases 8-10 and stabilize with full test coverage.
- Workstreams:
  - [x] Search settings, query tracking, and FTS index support
  - [x] Analytics events/daily aggregation + queue job
  - [x] Apps/webhooks/oauth baseline models, services, and jobs
  - [x] Admin sections for pages/navigation/themes/search/analytics/apps/developers
  - [x] Full Pest suite replacement for implemented shop behavior
  - [x] Pest run green (`29 passed`)
  - [x] Playwright acceptance walkthrough and bug-fix loop on `http://shop.test`

## Iteration 4 - Acceptance loop fixes + final verification
- Status: completed
- Goal: close spec gaps found during full browser walkthrough and finalize validation.
- Workstreams:
  - [x] Fixed storefront product-card inventory labels for `continue` policy (`Available on backorder` + purchasable state)
  - [x] Added Pest coverage for backorder rendering behavior on storefront cards
  - [x] Implemented dedicated admin auth routes (`/admin/login`, `/admin/logout`) with rate-limited login attempts
  - [x] Added admin authentication feature tests (render/login/invalid/throttle/logout/redirect)
  - [x] Relaxed overly strict `email:rfc,dns` validation to `email` for local `.test` credentials in admin/customer/checkout auth flows
  - [x] Re-ran full Pest suite green (`36 passed`, `257 assertions`)
  - [x] Ran full Playwright acceptance walkthrough (customer + admin):
    - Storefront browse/search/cart/discounts/checkout (credit success + decline + bank transfer pending)
    - Customer account login, address create/default/delete, orders page, logout
    - Admin login/logout, product create/edit, order payment confirmation + fulfillment + refund, discounts/search/analytics/apps/developers/settings page checks
  - [x] Browser console error check clean (0 errors)

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
- [x] Phase 11 Polish
- [x] Phase 12 Full test + Playwright verification

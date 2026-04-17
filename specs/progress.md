# Shop Implementation Progress

Started: 2026-04-17
Branch: 2026-04-16-claude-code-opus-4-7-strict-prompt
Shop URL: http://shop.test/

## Status Legend
- [ ] Not started
- [~] In progress
- [x] Done (implemented + tested)

## Phases

### Phase 1: Foundation
- [x] 1.1 Environment and Config
- [x] 1.2 Core Migrations (organizations, stores, store_domains, store_users, store_settings, users modifications)
- [x] 1.3 Core Models + factories + seeders
- [x] 1.4 Enums (StoreStatus, StoreUserRole, StoreDomainType)
- [x] 1.5 Tenant Resolution Middleware (ResolveStore)
- [x] 1.6 BelongsToStore trait + StoreScope
- [x] 1.7 Authentication (admin + customer guards)
- [x] 1.8 Authorization policies

### Phase 2: Catalog
- [x] Products / Variants / Options / Inventory / Collections / Media

### Phase 3: Themes / Pages / Navigation / Storefront Layout
- [ ] Themes + CMS + Storefront Blade layouts

### Phase 4: Cart / Checkout / Discounts / Shipping / Taxes
- [ ]

### Phase 5: Payments / Orders / Fulfillment
- [ ]

### Phase 6: Customer Accounts
- [ ]

### Phase 7: Admin Panel
- [ ]

### Phase 8: Search
- [ ]

### Phase 9: Analytics
- [ ]

### Phase 10: Apps & Webhooks
- [ ]

### Phase 11: Polish
- [ ]

### Phase 12: Full Test Suite (Pest + Playwright E2E)
- [ ]

## Log
- 2026-04-17: Starting implementation with team mode.
- 2026-04-17: Phase 1 complete. Kept users.password column name (not renamed to password_hash) to preserve Fortify starter-kit tests; override not required. Added /admin and /account route groups alongside existing Fortify routes. Customer guard registered via CustomerUserProvider which falls back to User model until Phase 6 introduces Customer.

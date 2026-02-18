# Shop Implementation Progress

## Build Order (from specs/09-IMPLEMENTATION-ROADMAP.md)

### Dependency Graph
```
Phase 1 (Foundation) --> Phase 2 (Catalog) + Phase 3 (Themes/Storefront)
Phase 2 + Phase 3 --> Phase 4 (Cart/Checkout/Discounts/Shipping/Taxes)
Phase 4 --> Phase 5 (Payments/Orders/Fulfillment)
Phase 5 --> Phase 6 (Customer Accounts) + Phase 7 (Admin Panel) + Phase 9 (Analytics) + Phase 10 (Apps/Webhooks)
Phase 2 --> Phase 8 (Search) [via Phase 2+3 QA]
Phase 6 + Phase 7 + Phase 8 + Phase 9 + Phase 10 --> Phase 11 (Polish)
Phase 11 --> Phase 12 (Full Test Suite + API Routes)
Phase 12 --> Phase 13 (Quality Gates) --> Phase 14 (E2E) --> Phase 15 (Final Review) --> Phase 16 (SonarCloud) --> Phase 17 (Final Showcase)
```

### Parallelization Opportunities
- Phase 2 (Catalog) and Phase 3 (Themes/Storefront) can run in parallel after Phase 1
- Phase 6, 7, 8, 9, 10 can partially parallelize after Phase 5
- QA tasks (PHPStan, Deptrac, Pest) run in parallel after migrate:fresh --seed

## Quality Rules

### PHPStan
- Level: max
- Path: app/
- Tmp dir: storage/phpstan
- Max 1 parallel process

### Deptrac Layers
- Models -> Enums
- Services -> Models, Enums, Events
- Controllers -> Services, Models, Enums, FormRequests
- Livewire -> Services, Models, Enums
- Policies -> Models, Enums
- FormRequests -> Models, Enums
- Listeners -> Services, Models, Enums
- Middleware -> Models, Enums, Services

## Decisions on Ambiguities

1. **Password field naming**: Spec says "rename password to password_hash internally" but Laravel's built-in auth expects `password`. Decision: Keep the column as `password` in the database for compatibility with Laravel's auth system. The spec's intent is to store hashed passwords, which Laravel does by default.

2. **Store resolution for admin**: Admin routes use session-based store resolution. The first store the admin user belongs to will be set as default on login. Users can switch stores via the TopBar dropdown.

3. **Cart session binding**: Guest carts are bound via Laravel session ID stored in the `session_id` column on the carts table.

4. **API versioning**: All API routes are versioned as v1 under `/api/storefront/v1/` and `/api/admin/v1/`.

5. **FTS5 migration**: Will use raw SQL in a migration since Laravel's schema builder doesn't support FTS5 virtual tables.

6. **Theme editor**: Will be a basic section-based editor without live preview iframe. Settings are stored as JSON.

## Phase Status

| Phase | Status | Started | Completed | Notes |
|-------|--------|---------|-----------|-------|
| 0 - Orientation | Complete | 2026-02-18 | 2026-02-18 | Tasks created, dependencies set |
| 1 - Foundation | Pending | | | |
| 2 - Catalog | Pending | | | |
| 3 - Themes/Storefront | Pending | | | |
| 4 - Cart/Checkout | Pending | | | |
| 5 - Payments/Orders | Pending | | | |
| 6 - Customer Accounts | Pending | | | |
| 7 - Admin Panel | Pending | | | |
| 8 - Search | Pending | | | |
| 9 - Analytics | Pending | | | |
| 10 - Apps/Webhooks | Pending | | | |
| 11 - Polish | Pending | | | |
| 12 - Full Test Suite | Pending | | | |
| 13 - Quality Gates | Pending | | | |
| 14 - E2E | Pending | | | |
| 15 - Final Review | Pending | | | |
| 16 - SonarCloud | Pending | | | |
| 17 - Final Showcase | Pending | | | |

## QA Results

(Updated after each QA cycle)

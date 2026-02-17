# Implementation Progress

## Build Order (from specs/09-IMPLEMENTATION-ROADMAP.md)

### Phase Dependency Graph
```
P1 (Foundation) --> P2 (Catalog), P3 (Themes/Storefront)
P2 + P3 --> P4 (Cart/Checkout/Discounts/Shipping/Taxes)
P4 --> P5 (Payments/Orders/Fulfillment)
P5 --> P6 (Customer Accounts), P7 (Admin Panel), P9 (Analytics), P10 (Apps/Webhooks)
P2 --> P8 (Search)
P6 + P7 + P8 + P9 + P10 --> P11 (Polish)
P11 --> P12 (Full Test Suite)
```

### Parallelization Plan
- Phase 1: Sequential (everything depends on it)
- Phases 2 + 3: Can run in parallel after Phase 1
- Phase 4: After Phases 2 + 3
- Phase 5: After Phase 4
- Phases 6 + 7 + 8 + 9 + 10: Can run in parallel after Phase 5 (8 only needs P2)
- Phase 11: After Phases 6-10
- Phase 12: After Phase 11

## Risk Areas

1. **SQLite locking**: Only one teammate runs migrations at a time via the "qa" role
2. **File ownership conflicts**: Each teammate owns distinct files; no two edit the same file
3. **Tenant scoping**: BelongsToStore trait + StoreScope must be applied early and consistently
4. **Pricing math**: All monetary values in integer cents; rounding must be deterministic
5. **Cart versioning**: Optimistic concurrency via cart_version field
6. **FTS5**: SQLite-specific virtual table requires raw SQL migration

## Decisions on Ambiguities

1. **Password column name**: Spec says "rename password to password_hash internally" but Laravel's auth system expects `password`. Decision: Keep `password` as column name to maintain compatibility with Laravel auth. Do not rename.
2. **Order numbering**: Sequential per store starting at #1001. Use a simple `MAX(order_number) + 1` approach with a DB transaction lock.
3. **Theme editor**: Simplified to settings-only editor (no code editing). Theme files store Blade templates but the editor UI manages settings JSON.
4. **OAuth/Passport**: Deferred as per spec. Apps table and webhook subscriptions exist but OAuth flow is stubbed.
5. **Admin auth vs Fortify**: Existing Fortify setup handles admin (User model) auth. We'll adapt the existing login flow for the admin panel rather than creating a separate auth system.
6. **Customer guard**: Custom guard using `CustomerUserProvider` that scopes by store_id. Completely separate from User model auth.
7. **API rate limiting**: Storefront 120/min, Admin 60/min as mentioned in specs.
8. **Media processing**: Synchronous in dev (sync queue). ProcessMediaUpload job handles image resizing.
9. **Checkout expiry**: 30 minutes from payment_selected state. ExpireAbandonedCheckouts runs every 15 minutes.
10. **Bank transfer auto-cancel**: 7 days default, configurable via store settings.

## Phase Progress

| Phase | Status | Notes |
|-------|--------|-------|
| Phase 0: Orientation | Complete | Specs read, plan created, team spawned |
| Phase 1: Foundation | Complete | Config, migrations, models, enums, middleware, auth, policies |
| Phase 2: Catalog | Complete | Products, variants, inventory, collections, media, services |
| Phase 3: Themes/Storefront | Complete | Themes, pages, navigation, storefront layout, Livewire components |
| Phase 4: Cart/Checkout | Complete | Cart, checkout, discounts, shipping, taxes, pricing engine |
| Phase 5: Payments/Orders | Complete | Mock PSP, orders, fulfillments, refunds, events |
| Phase 6: Customer Accounts | Complete | Customer dashboard, orders, addresses |
| Phase 7: Admin Panel | Complete | Full admin panel with all CRUD operations |
| Phase 8: Search | Complete | FTS5 search, autocomplete, search results |
| Phase 9: Analytics | Complete | Event tracking, daily aggregation |
| Phase 10: Apps/Webhooks | Complete | Webhook service, delivery job, circuit breaker |
| Phase 11: Polish | Complete | Error pages, accessibility, scheduled jobs, seeders |
| Phase 12: Full Test Suite | Complete | All unit and feature tests |
| Quality Gates | Complete | Pint, PHPStan, Pest all passing |
| Code Review | Complete | 3 critical, 6 major, 9 minor findings - all critical/major fixed |

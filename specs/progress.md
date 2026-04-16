# Shop Implementation Progress

Last updated: 2026-04-16

## Strategy

Large scope (9 spec files, 660KB total). Approach:
1. Vertical slice first — critical path that lets a customer buy a product end-to-end.
2. Broaden into admin, customer accounts, search, analytics, apps.
3. Pest tests alongside each module; Playwright smoke tests at the end.
4. Team mode used for parallel-eligible work (models vs views, tests vs implementation).

## Phases

- [ ] Phase 1 — Foundation (organizations, stores, users, tenancy, auth)
- [ ] Phase 2 — Catalog (products, variants, inventory, collections, media)
- [ ] Phase 3 — Themes + storefront layout
- [ ] Phase 4 — Cart, checkout, discounts, shipping, taxes
- [ ] Phase 5 — Payments, orders, fulfillment
- [ ] Phase 6 — Customer accounts
- [ ] Phase 7 — Admin panel
- [ ] Phase 8 — Search (SQLite FTS)
- [ ] Phase 9 — Analytics
- [ ] Phase 10 — Apps & webhooks (stubs)
- [ ] Phase 11 — Polish
- [ ] Phase 12 — Full test suite + Playwright E2E

# Shop Implementation Progress

Last updated: 2026-04-16

## Phases

- [x] Phase 1 — Foundation (organizations, stores, users, tenancy, auth)
- [x] Phase 2 — Catalog (products, variants, inventory, collections, media)
- [x] Phase 3 — Themes + storefront layout
- [x] Phase 4 — Cart, checkout, discounts, shipping, taxes
- [x] Phase 5 — Payments, orders, fulfillment
- [x] Phase 6 — Customer accounts
- [x] Phase 7 — Admin panel
- [x] Phase 8-10 — Search, analytics tracking, apps/webhooks stubs
- [x] Phase 11-12 — Seeders, Pest tests, Playwright E2E

## Verification

- Fresh `migrate:fresh --seed` runs cleanly with demo data.
- 26 Pest tests pass (unit + feature): pricing, inventory, payment provider,
  storefront smoke tests, full cart-to-order journey, tenant isolation.
- Playwright browser journey confirmed end-to-end:
  home → product detail → add to cart → discount code → checkout → order
  confirmation #1006. Admin login, dashboard, order detail, fulfillment
  (with tracking) all work in-browser.

## Shortcuts / deviations from the spec

- Theme editor stubbed. Themes table exists, a Default theme is seeded,
  but the in-admin section/block editor is not implemented.
- Apps marketplace + OAuth + webhook deliveries are DB-only stubs. No
  admin UI for registering apps yet.
- Search uses SQL LIKE across title/description/vendor/type instead of
  SQLite FTS5. Tracking of searches is persisted in `search_queries`.
- Analytics events table exists and is seed-ready; there is no admin
  analytics dashboard page yet (the main Dashboard shows KPIs instead).
- Media upload uses a simple record-only flow; image resize job is not
  wired (placeholder product cards render without uploaded images).
- Email is log-driver only; no order confirmation emails are queued.
- API token endpoints (Sanctum personal access tokens) are not exposed.

# Shop Build Progress

Mission: implement entire shop system from `specs/*.md` from scratch (branch `2026-09-13-cursor-muse-spark-1-3`).
Stack: PHP 8.4 / Laravel 12 / Livewire v4 / Flux UI Free v2 / Tailwind v4 / SQLite / Pest v4.
Schema: managed ONLY in `draft.yaml` via Laravel Blueprint (`php artisan blueprint:erase && php artisan blueprint:build && php artisan migrate:fresh --seed`).

## Status Legend
- [ ] pending, [~] in progress, [x] done, [!] blocked/bug

## Phases
- [~] Setup: progress.md, install Sanctum+Blueprint, base config
- [ ] Agent A: Full `draft.yaml` schema (spec 01) + models + enums + factories
- [ ] Agent B: Foundation - tenant resolution, middleware, BelongsToStore, admin/customer auth, RBAC (specs 05.1, 06, 09 Phase 1)
- [ ] Agent C: Catalog - products, variants, inventory, collections, media, search FTS5 (specs 05.2-3, 09 Phase 2+8)
- [ ] Agent D: Cart, pricing engine, discounts, shipping, tax, checkout state machine, mock PSP payments, orders, refunds, fulfillment (specs 05.4-12, 09 Phase 4+5)
- [ ] Agent E: Storefront UI - layout, themes, pages, navigation, cart/checkout UI, customer accounts (specs 04, 09 Phase 3+6)
- [ ] Agent F: Admin panel (spec 03), analytics, webhooks/apps, seeders (specs 07, 09 Phase 7+9+10)
- [ ] Agent G (independent 2nd agent): verify ALL acceptance criteria via Pest + Playwright MCP, file bugs
- [ ] Bugfix + final review meeting (customer- + admin-side showcase)

## Log
- 2026-09-13 08:53 UTC+2: Mission started. Branch is fresh Livewire starter kit (Fortify auth, no shop code) - building from scratch confirmed.

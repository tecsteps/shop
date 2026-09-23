# Shop implementation progress

The implementation follows the build order in `09-IMPLEMENTATION-ROADMAP.md` and acceptance details in the other `specs/*.md` files. Each completed iteration is committed separately.

## Phases

- [ ] Foundation: tenant schema, store resolution, authentication, authorization
- [ ] Catalog: products, variants, inventory, collections, media
- [ ] Themes, pages, navigation, storefront layout
- [ ] Cart, checkout, discounts, shipping, taxes
- [ ] Payments, orders, refunds, fulfillment
- [ ] Customer accounts and addresses
- [ ] Admin panel and settings
- [ ] Search
- [ ] Analytics
- [ ] Apps, API tokens, and outbound webhooks
- [ ] Accessibility, security, responsive polish, and error handling
- [ ] Pest unit and feature coverage
- [ ] Playwright acceptance review and independent second-agent audit

## Iterations

### 2026-09-23 — Repository and requirements audit

- Confirmed the working tree was clean and the app was the Laravel starter kit; no shop implementation is present in this checkout.
- Read the roadmap and project-local instructions; `.ai/rules` does not exist.
- Confirmed Laravel 12.51, Livewire 4.1, Flux UI 2.12, Tailwind 4, and Pest 4.3 are installed. The Pest browser plugin and Sanctum are not installed.
- Existing Pest baseline passed: 33 tests, 75 assertions.
- Resolved the Herd URL as `http://shop.test`; Playwright MCP is available for the acceptance review.
- Delegated independent spec acceptance mapping and browser infrastructure inspection.

### 2026-09-23 — Initial shop foundation and storefront slice

- Added the tenant-aware commerce schema, store resolver, store-scoped models, role policies, catalog/cart/checkout/payment/order/refund/fulfillment services, storefront and admin route/component skeletons, API token middleware, and two-store demo seeding.
- Added the responsive storefront and admin Blade surfaces. The storefront supports the catalog, product detail, cart, checkout, account, search, page, and confirmation views; admin screens are being wired to complete data/actions in the next iteration.
- Added a newsletter subscription table/action and HTML sanitizer for merchant-authored page/theme content.
- Enabled `RefreshDatabase` for feature tests, restored the starter settings routes, and fixed full-page Livewire view composition uncovered by the first migrated test run.
- Chose `payment_selected` as the checkout reservation state, normalized `country_code`/`country` and `zip`/`postal_code` address aliases, used signed timestamp plus body for webhook HMAC, and followed the per-product seed matrix count of 117 variants.
- Known work remaining: complete missing admin actions/routes and password reset/logout flows, finish API request/response/ability contracts, add webhook delivery and analytics aggregation, add comprehensive shop tests, and run customer/admin Playwright scenarios plus independent acceptance audit.

**Checks this iteration:** fresh SQLite migration + demo seeding succeeded; initial Pest suite passed (33 tests, 75 assertions); Pint passed; admin Blade compilation and `npm run build` passed in the view agent's check.

### 2026-09-23 — Tenant contracts, checkout/account flows, analytics and webhooks

- Reconciled storefront API guest-cart access, cart-version requirements, discount allocation recalculation, checkout address aliases, digital-only checkout, payment retry, expiration, and API error codes with the route/business specs.
- Added store-scoped customer password reset through Laravel's password broker, a tenant-bound token repository/provider, generic reset-link acknowledgements, and admin/customer recovery pages. Removed Fortify's public registration and generic reset endpoints so they cannot bypass the shop-specific flows; made logout a CSRF-protected POST.
- Corrected tax rounding to calculate each item and taxable shipping line independently before summing, as required by the pricing rules.
- Added daily analytics aggregation and queued outbound webhook delivery with encrypted signing secrets, HMAC-SHA256 signatures, delivery history, retries, response snippets, and pause after five consecutive failures. Analytics schema now contains canonical event fields and completed-checkout counts.
- Added reusable model factories and focused feature/unit coverage for tenant resolution, catalog lifecycle, guest cart version/stock errors, checkout/payment/inventory transitions, customer registration/login/address ownership/reset isolation, admin API abilities, pricing/discount/tax rounding, HTML sanitization, analytics aggregation, and webhook delivery/signatures/retries.
- Progress phases remain unchecked until the remaining routes/admin actions, search/settings, media, scheduled cleanup, and browser acceptance work are finished.

**Checks this iteration:** 39 focused shop tests passed (156 assertions); `vendor/bin/pint --dirty --format agent` passed; `npm run build` passed; route list confirmed the new shop recovery endpoints and absence of Fortify's generic recovery routes.

## Verification log

| Date | Check | Result |
|------|-------|--------|
| 2026-09-23 | Existing Pest suite (`php artisan test --compact`) | Passed: 33 tests, 75 assertions |
| 2026-09-23 | Fresh migration + demo seed (`php artisan migrate:fresh --seed --force --no-interaction`) | Passed on SQLite; 2 stores, 117 fashion variants, and 18 seeded orders |
| 2026-09-23 | Pest after tenant test setup and Livewire view fixes | Passed: 33 tests, 75 assertions |
| 2026-09-23 | `vendor/bin/pint --dirty --format agent` | Passed |
| 2026-09-23 | Admin Blade compilation and `npm run build` | Passed in the view agent's check |
| 2026-09-23 | Focused tenant, catalog, cart, checkout, account, API, pricing, analytics, and webhook Pest tests | Passed: 39 tests, 156 assertions |
| 2026-09-23 | `npm run build` after account recovery views | Passed |
| 2026-09-23 | `php artisan route:list --path=forgot` and `--path=reset` | Passed; both admin and customer shop routes registered, generic Fortify reset routes removed |

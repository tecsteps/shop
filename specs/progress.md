# Shop Implementation Progress

Last updated: 2026-05-03

## Objective

Build the complete self-contained shop platform from `specs/*` and verify it with Pest plus browser smoke flows at `http://shop.test/`.

## Current State

- Repository started from the Laravel Livewire starter kit with Fortify authentication.
- Phase 1 foundation is implemented and committed: configuration defaults, core tenancy schema, models, factories, seeders, tenant middleware, customer guard provider registration, store role helper, and password_hash compatibility.
- Phase 2 catalog backend is implemented and committed: products, options, option values, variants, inventory, collections, collection pivot, media schema/models/factories/seed data, product lifecycle service, variant matrix service, admin multi-option variant builder, inventory service, handle generator, and product/collection policies.
- Product media processing now generates resized image variants (`thumbnail`, `small`, `medium`, `large`) plus WebP sidecars when supported, records original metadata, fails invalid/missing originals, retries up to three times, and cleans up original/generated files when media is deleted.
- Phase 3 theme/storefront shell is implemented and verified: theme/page/navigation schema, models, factories, seed data, theme settings service, configurable storefront homepage section ordering/visibility, navigation service, storefront layout, product cards, price rendering, richer 404/503 error templates, and Livewire storefront pages.
- Phase 4 cart/checkout/pricing is implemented and verified: carts, cart lines, checkouts, shipping zones/rates, tax settings, discounts, cart and checkout services, persisted cart discount codes with line allocations, pricing snapshots, storefront REST endpoints, Livewire cart/checkout UI, and cleanup jobs.
- Phase 5 payments/orders/customer persistence is implemented and verified: customers, customer addresses, orders, order lines, payments, refunds, fulfillments, mock PSP, checkout pay endpoint/UI, order confirmation, bank-transfer confirmation/cancellation services, and focused tests.
- Phase 6 customer accounts are implemented and verified: store-scoped customer login/registration, customer password reset links/emails, account dashboard, order history/detail pages, address book CRUD, and seeded customer credentials.
- Phase 7 admin panel is implemented and verified: admin login, admin shell/store switcher, dashboard, product/order/customer/discount/inventory/settings/theme/page/navigation surfaces, analytics, apps, developer, and search-settings pages.
- SQLite FTS5 search is implemented and verified: search settings/query tables, product FTS indexing, product observer sync, storefront search page, header search modal, search API, seeded synonyms/stop words, and admin reindex action.
- Analytics ingestion and aggregation are implemented and verified: storefront batch event API, client event deduplication, seeded daily/event analytics, daily aggregation job, admin analytics summary API, and admin analytics dashboard.
- Apps, API tokens, and webhooks are implemented and verified: apps/installations/OAuth metadata, developer API token generation/revocation, spec-backed token ability selection, store-scoped token middleware, webhook subscriptions, signed delivery jobs, retry/failure tracking, and app admin screens.
- Demo seed data now covers the browser-plan fixture contract while preserving `shop.test`: two stores/domains, admin aliases, 20 fashion products, 5 electronics products, sold-out/backorder/draft product edges, five discount codes, 10+2 customers, and 15+3 orders.
- Storefront product stock states and the slide-out cart drawer are implemented and verified: sold-out `deny` variants disable add-to-cart, backorder `continue` variants add successfully, the cart drawer opens from add-to-cart events, quantity mutations reuse the versioned cart service, discount codes apply/remove inline, and the mobile storefront header fits small viewports.
- Admin order fulfillment/refund workflow now supports selected line quantities, line-derived refund amounts, selected restocking, fulfillment creation, shipped/delivered shipment transitions, tracking links, shipment events, and verified desktop/mobile order-detail behavior.
- Storefront checkout now uses a verified address, shipping, and payment step flow with locked future steps, editable completed steps, compact step summaries, and responsive order summary behavior.
- Admin Product REST API endpoints are implemented and verified: token-scoped list/show/create/update/archive routes, product JSON resources, store-scoped validation, variant inventory mutation, collection assignment, and ability/store isolation tests.
- Admin Order REST API endpoints are implemented and verified: token-scoped list/show routes, shipped fulfillment creation, captured-payment refunds, nested order JSON resources, validation, and ability/store isolation tests.
- Product lifecycle order-reference guards are now verified against real `order_lines`: draft reversion/deletion is blocked for referenced products, unreferenced draft products hard-delete, and orphan variants with order references are archived.
- Admin Collection and Discount REST API endpoints are implemented and verified: token-scoped collection CRUD with product assignment, discount CRUD with scoped code validation, JSON resources, and cross-store isolation.
- Admin Settings REST API endpoints are implemented and verified: shipping zone list/create/update, shipping-rate creation, tax settings show/update, overlap validation, JSON resources, and token/store isolation.
- Admin Content and Search Maintenance REST API endpoints are implemented and verified: token-scoped page CRUD with sanitized HTML/generated handles, store-scoped validation, search index status reporting, synchronous FTS reindex behind the spec's accepted-job response, and ability/store isolation tests.
- Admin Theme REST API endpoints are implemented and verified: ZIP theme archive import with `theme.json` manifest validation, persisted theme files, settings updates, publish transitions, cache invalidation, and token/store isolation tests.
- Admin Export REST API endpoints are implemented and verified: queued-style order export creation, sync-capable CSV generation, persisted export records/files, status/download metadata, filter validation, and token/store isolation tests.
- Admin Platform REST API endpoints are implemented and verified: manage-platform organization/store creation, owner-scoped platform token authorization, store invites, current token membership metadata, validation, duplicate membership conflicts, and store isolation tests.

## Execution Plan

1. **Foundation and tenancy** - Committed (`2ebb8ed4`)
   - Configure SQLite/file/sync environment defaults.
   - Add organization/store/domain/settings schema, models, enums, factories, seeders, tenant middleware, store-scoped model support, customer guard baseline, and role-aware user helpers.
   - Verify with focused tenancy/model/auth tests and `migrate:fresh --seed`.
2. **Catalog** - Committed (`ea70780e`)
   - Add products, variants, options, inventory, collections, media, catalog services, and storefront/admin catalog basics.
   - Verify product lifecycle, inventory, variants, and collection isolation tests.
3. **Theme and storefront shell** - Committed (`58879dde`)
   - Add themes, pages, navigation, storefront layout, reusable components, and initial storefront pages.
   - Verify storefront render, navigation, product detail, accessibility smoke, and responsive browser checks.
4. **Cart, checkout, pricing** - Implemented and verified
   - Add carts, checkout, discounts, shipping, taxes, pricing engine, state transitions, and REST endpoints.
   - Verify cart API, cart UI, checkout state, pricing, discount, shipping, and tax tests.
5. **Payments and orders** - Implemented and verified
   - Add customers, addresses, orders, payments, refunds, fulfillments, mock PSP, order events, and scheduled cleanup jobs.
   - Verify successful card checkout, declined card, bank transfer pending/confirmation, fulfillment guard, refunds, and inventory commits/releases.
6. **Customer accounts** - Implemented and verified
   - Add store-scoped customer auth, account dashboard, order history, and address book.
   - Verify customer registration/login isolation and account browser flows.
7. **Admin panel** - Implemented and verified
   - Add admin shell, dashboard, resource management pages, settings, themes, pages, navigation, analytics, apps, and developers surfaces.
   - Verify admin login, store switching, product/order/discount/settings flows.
8. **Search, analytics, apps, webhooks** - Implemented and verified
   - SQLite FTS5 search is implemented and verified.
   - Analytics ingestion/aggregation, API token support, app installs, webhook dispatch/signing/delivery are implemented and verified.
9. **Polish and completion audit** - Pending
   - Run full Pest suite, style formatting, fresh migration/seeding, Playwright customer/admin flows, responsive and browser log review.
   - Update this file with final evidence and close all gaps.

## Decisions

- Implement in vertical slices following `specs/09-IMPLEMENTATION-ROADMAP.md`.
- Use SQLite, file cache/session, sync queue, log mail, and local filesystem as specified.
- Keep all money as integer minor units.
- Prefer Laravel conventions and existing starter-kit patterns unless specs require a different shop-specific behavior.
- Phase 1 customer guard/provider is registered now; `customers` persistence landed in Phase 5.
- Admin users store credentials in `users.password_hash`; the `User` model keeps a `password` attribute alias so Fortify and starter-kit Livewire settings continue to work.
- Storefront pages are class-based Livewire components using the existing starter layout conventions and store resolution middleware.
- Phase 3 includes a cart page/drawer shell only; persistent carts and line-item actions stay in Phase 4 so pricing and checkout state can be implemented coherently.
- Livewire update requests persist `ResolveStore` middleware so storefront actions keep tenant context after the initial page load.
- Phase 5 adds the `customers` table immediately before cart/checkout migrations and converts `carts.customer_id` and `checkouts.customer_id` to nullable foreign keys for fresh installs.
- `orders.checkout_id` is intentionally added beyond the table list so `OrderService::createFromCheckout()` can enforce idempotency with a durable unique key.
- Admin authentication uses a dedicated Livewire `/admin/login` screen on the existing `web` guard while leaving the starter-kit Fortify `/login` flow intact for existing auth/settings tests.
- API token requirements mention Sanctum, but Sanctum is not installed and dependencies cannot be changed without approval. The developers/API slice uses a first-party hashed-token table and route middleware to satisfy store-scoped token generation, revocation, and ability checks without adding dependencies.
- Storefront order-status API access uses an HMAC token derived from store, order id, and order number because confirmation/status URLs are public and should not require customer login.
- Customer password resets use the `customers` password broker with a store-scoped token repository so `customer_password_reset_tokens` keeps the spec-required `(store_id, email)` key. Fortify user password reset paths are mapped to `/admin/forgot-password` and `/admin/reset-password/{token}` to avoid route conflicts with storefront customer reset URLs.

## Open Gaps

- Discounts, shipping, and tax are implemented for the specified local/manual flows; provider/carrier integrations remain stubs by design.
- Admin analytics, apps, API tokens, and webhook backend flows are implemented for the current data model. The developer screen exposes the current spec-backed token abilities, including theme/content/settings scopes and owner-only `manage-platform`. A future dependency decision could replace the first-party token table with Sanctum if package changes are approved.

## Verification Log

- 2026-05-03: `php artisan test --compact tests/Feature/Foundation/FoundationModelTest.php tests/Feature/Tenancy/TenantResolutionTest.php` passed, 7 tests / 14 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Auth tests/Feature/Settings/PasswordUpdateTest.php tests/Feature/Settings/ProfileUpdateTest.php` passed, 25 tests / 62 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after creating missing `database/database.sqlite`.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed and fixed import ordering.
- 2026-05-03: `php artisan test --compact` passed, 40 tests / 89 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Products` passed, 9 tests / 30 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with catalog migrations and seed data.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed and fixed generated catalog PHP files.
- 2026-05-03: `php artisan test --compact` passed, 49 tests / 119 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront tests/Feature/ExampleTest.php` passed, 5 tests / 21 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with theme, page, and navigation migrations/seed data.
- 2026-05-03: `php artisan test --compact` passed, 53 tests / 139 assertions.
- 2026-05-03: `npm run build` passed for the storefront Tailwind/Vite assets.
- 2026-05-03: Playwright smoke visited `http://shop.test/` and `http://shop.test/products/linen-shirt`; latest browser console check reported no warnings or errors.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/StorefrontCartApiTest.php tests/Feature/Cart tests/Feature/Checkout tests/Feature/Storefront/StorefrontCartLivewireTest.php` passed, 7 tests / 44 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with cart, checkout, shipping, tax, and discount migrations/seed data.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Phase 4 changes.
- 2026-05-03: `php artisan test --compact` passed, 60 tests / 183 assertions.
- 2026-05-03: `npm run build` passed for the updated cart/checkout Tailwind/Vite assets.
- 2026-05-03: Playwright smoke completed product add-to-cart, cart checkout start, checkout address save, shipping selection, and payment-method save at `http://shop.test`; latest console check reported no warnings or errors.
- 2026-05-03: `browser_logs` reported no browser log file after the latest smoke check.
- 2026-05-03: `php artisan test --compact tests/Feature/Payments/MockPaymentProviderTest.php tests/Feature/Orders/OrderServiceTest.php tests/Feature/Api/StorefrontCheckoutPaymentApiTest.php` passed, 13 tests / 79 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed and fixed generated seeder imports plus route/controller import ordering.
- 2026-05-03: `php artisan test --compact` passed, 73 tests / 262 assertions.
- 2026-05-03: `npm run build` passed for the updated payment/confirmation checkout UI assets.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with customers, orders, payments, refunds, and fulfillment migrations/seed data.
- 2026-05-03: Playwright smoke completed product add-to-cart, cart checkout start, checkout address save, shipping selection, credit-card payment, and order confirmation at `http://shop.test`; latest console check reported no warnings or errors.
- 2026-05-03: `browser_logs` reported no browser log file after the latest Phase 5 smoke check.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/CustomerAccountTest.php` passed, 6 tests / 26 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Phase 6 account changes.
- 2026-05-03: `php artisan test --compact` passed, 79 tests / 288 assertions.
- 2026-05-03: `npm run build` passed for the updated account UI assets.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with customer login and account seed data.
- 2026-05-03: Playwright smoke completed customer login, account dashboard, order history, order detail, and address book creation at `http://shop.test`; latest console check reported no warnings or errors.
- 2026-05-03: `browser_logs` reported no browser log file after the latest Phase 6 smoke check.
- 2026-05-03: `php artisan route:list --path=admin --except-vendor` passed and showed 31 admin routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Admin/AdminPanelTest.php` passed, 6 tests / 67 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Phase 7 admin changes.
- 2026-05-03: `php artisan test --compact` passed, 85 tests / 355 assertions.
- 2026-05-03: `npm run build` passed for the updated admin Tailwind/Vite assets.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with admin routes and seeded admin/customer/order/catalog data.
- 2026-05-03: Playwright smoke completed admin login, dashboard, products list, and order detail at `http://shop.test/admin`; latest console check reported no new warnings or errors.
- 2026-05-03: `browser_logs` reported no browser log file after the latest Phase 7 smoke check.
- 2026-05-03: `php artisan test --compact tests/Feature/Search/SearchTest.php` passed, 5 tests / 24 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Search/SearchTest.php tests/Feature/Admin/AdminPanelTest.php tests/Feature/Storefront/StorefrontRenderTest.php` passed, 13 tests / 106 assertions.
- 2026-05-03: `php artisan route:list --path=api/storefront/v1/search --except-vendor` passed and showed the search and suggest API routes.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after the search changes.
- 2026-05-03: `php artisan test --compact` passed, 90 tests / 379 assertions.
- 2026-05-03: `npm run build` passed for the updated search UI assets.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with FTS5 search migrations, seeded search settings, and reindexed seeded products.
- 2026-05-03: Playwright smoke completed `/search?q=linen`, header search modal suggestions for `lin`, and `api/storefront/v1/search?q=linen` at `http://shop.test`; latest browser console checks reported no current warnings or errors.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with analytics, apps, OAuth metadata, webhooks, and API token migrations/seed data.
- 2026-05-03: `php artisan test --compact tests/Feature/Analytics/AnalyticsTest.php tests/Feature/Webhooks/WebhookDeliveryTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php` passed, 7 tests / 49 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Analytics/AnalyticsTest.php tests/Feature/Webhooks/WebhookDeliveryTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php tests/Feature/Admin/AdminPanelTest.php tests/Feature/Search/SearchTest.php tests/Feature/Api/StorefrontCartApiTest.php tests/Feature/Api/StorefrontCheckoutPaymentApiTest.php` passed, 23 tests / 190 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed and fixed generated seeder imports plus console route import ordering.
- 2026-05-03: `php artisan route:list --path=api --except-vendor` passed and showed 17 API routes, including storefront analytics ingestion and admin analytics summary.
- 2026-05-03: `php artisan test --compact` passed, 97 tests / 428 assertions.
- 2026-05-03: `npm run build` passed for the updated analytics/apps/developer admin UI assets.
- 2026-05-03: Playwright smoke completed admin analytics date-range interaction, developer API token generation, webhook creation, apps list, and app detail at `http://shop.test/admin`; latest browser console checks reported no warnings or errors.
- 2026-05-03: HTTP smoke through Herd returned `202` for `POST /api/storefront/v1/analytics/events` and `200` for token-protected `GET /api/admin/v1/stores/1/analytics/summary`.
- 2026-05-03: `php artisan test --compact tests/Feature/Security/HtmlSanitizerTest.php tests/Feature/Api/StorefrontOrderStatusApiTest.php` passed, 5 tests / 24 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed and fixed the new security test import list.
- 2026-05-03: `php artisan route:list --path=api/storefront/v1/orders --except-vendor` passed and showed the signed storefront order-status route.
- 2026-05-03: `php artisan test --compact` passed, 102 tests / 452 assertions.
- 2026-05-03: `npm run build` passed after adding styled error pages.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with expanded deterministic browser-plan seed data.
- 2026-05-03: `php artisan test --compact tests/Feature/Seeders/SeedDataContractTest.php` passed, 3 tests / 23 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Seeders/SeedDataContractTest.php tests/Feature/Search/SearchTest.php tests/Feature/Admin/AdminPanelTest.php tests/Feature/Storefront/StorefrontRenderTest.php tests/Feature/Api/StorefrontCheckoutPaymentApiTest.php tests/Feature/Storefront/CustomerAccountTest.php` passed, 25 tests / 181 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after expanded seeder changes.
- 2026-05-03: `php artisan test --compact` passed, 105 tests / 475 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed again after the final seeder adjustment.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/StorefrontCartLivewireTest.php tests/Feature/Search/SearchTest.php tests/Feature/Storefront/StorefrontRenderTest.php` passed, 12 tests / 66 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after storefront cart drawer, product stock state, search modal, and admin product sort changes.
- 2026-05-03: `php artisan test --compact` passed, 109 tests / 498 assertions.
- 2026-05-03: `npm run build` passed for the updated storefront drawer, product stock, search modal, and responsive header assets.
- 2026-05-03: Playwright smoke verified sold-out product disable state, backorder add-to-cart with drawer opening, drawer quantity controls, header search modal suggestions, and mobile drawer/header layout at `http://shop.test`; latest browser console checks reported no warnings or errors.
- 2026-05-03: `php artisan test --compact tests/Feature/Orders/OrderServiceTest.php tests/Feature/Admin/AdminPanelTest.php` passed, 14 tests / 123 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after admin fulfillment shipment workflow changes.
- 2026-05-03: `php artisan test --compact` passed, 111 tests / 517 assertions.
- 2026-05-03: `npm run build` passed for the updated admin order detail UI assets.
- 2026-05-03: Playwright smoke verified admin order #1001 fulfillment creation, mark as shipped, mark as delivered, tracking display, and mobile order-detail layout at `http://shop.test/admin/orders/1`; latest browser console checks reported no warnings or errors.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/CheckoutStepFlowTest.php tests/Feature/Storefront/StorefrontCartLivewireTest.php tests/Feature/Checkout/CheckoutServiceTest.php tests/Feature/Api/StorefrontCheckoutPaymentApiTest.php` passed, 10 tests / 67 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after checkout step-flow changes.
- 2026-05-03: `php artisan test --compact` passed, 112 tests / 528 assertions.
- 2026-05-03: `npm run build` passed for the updated checkout step-flow UI assets.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after the checkout step-flow full-suite verification.
- 2026-05-03: Playwright smoke verified desktop checkout address, shipping, payment, confirmation, and mobile checkout layout at `http://shop.test`; latest browser console checks reported no warnings or errors.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminProductApiTest.php` passed, 4 tests / 43 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1/stores --except-vendor` passed and showed the admin analytics summary plus product index/store/show/update/destroy routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminProductApiTest.php tests/Feature/Analytics/AnalyticsTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php tests/Feature/Products/ProductServiceTest.php` passed, 13 tests / 96 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Product API changes.
- 2026-05-03: `php artisan test --compact` passed, 116 tests / 571 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminOrderApiTest.php` passed, 5 tests / 49 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1/stores --except-vendor` passed and showed the admin analytics summary, product, and order REST routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Api/AdminProductApiTest.php tests/Feature/Orders/OrderServiceTest.php tests/Feature/Admin/AdminPanelTest.php tests/Feature/Analytics/AnalyticsTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php` passed, 28 tests / 253 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Order API changes.
- 2026-05-03: `php artisan test --compact` passed, 121 tests / 620 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminCollectionDiscountApiTest.php` passed, 3 tests / 35 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1/stores --except-vendor` passed and showed the admin analytics summary, product, order, collection, and discount REST routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminCollectionDiscountApiTest.php tests/Feature/Api/AdminProductApiTest.php tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Admin/AdminPanelTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php` passed, 21 tests / 223 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Collection and Discount API changes.
- 2026-05-03: `php artisan test --compact` passed, 124 tests / 655 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminSettingsApiTest.php` passed, 3 tests / 26 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1/stores --except-vendor` passed and showed the admin analytics summary, product, order, collection, discount, shipping, and tax REST routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminSettingsApiTest.php tests/Feature/Api/AdminCollectionDiscountApiTest.php tests/Feature/Api/AdminProductApiTest.php tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Admin/AdminPanelTest.php tests/Feature/Checkout/CheckoutServiceTest.php` passed, 24 tests / 240 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Settings API changes.
- 2026-05-03: `php artisan test --compact` passed, 127 tests / 681 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1 --except-vendor` passed and showed 30 admin API routes, including pages and search maintenance.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Content and Search Maintenance API changes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminContentSearchApiTest.php` passed, 3 tests / 47 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminProductApiTest.php tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Api/AdminCollectionDiscountApiTest.php tests/Feature/Api/AdminSettingsApiTest.php tests/Feature/Api/AdminContentSearchApiTest.php tests/Feature/Search/SearchTest.php` passed, 24 tests / 230 assertions.
- 2026-05-03: `php artisan test --compact` passed, 130 tests / 728 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after expanding developer API token abilities.
- 2026-05-03: `php artisan test --compact tests/Feature/Developers/DeveloperIntegrationsTest.php` passed, 3 tests / 34 assertions.
- 2026-05-03: `php artisan test --compact` passed, 131 tests / 744 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1 --except-vendor` passed and showed 33 admin API routes, including theme import, publish, and settings endpoints.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Theme API changes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminThemeApiTest.php` passed, 3 tests / 24 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminThemeApiTest.php tests/Feature/Api/AdminContentSearchApiTest.php tests/Feature/Api/AdminSettingsApiTest.php tests/Feature/Api/AdminCollectionDiscountApiTest.php tests/Feature/Api/AdminProductApiTest.php tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php tests/Feature/Storefront/ThemeNavigationTest.php` passed, 26 tests / 263 assertions.
- 2026-05-03: `php artisan test --compact` passed, 134 tests / 768 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1 --except-vendor` passed and showed 35 admin API routes, including order export creation and status endpoints.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Export API changes.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with the new `exports` table.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminExportApiTest.php` passed, 3 tests / 22 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminExportApiTest.php tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Api/AdminThemeApiTest.php tests/Feature/Api/AdminContentSearchApiTest.php tests/Feature/Api/AdminSettingsApiTest.php tests/Feature/Api/AdminCollectionDiscountApiTest.php tests/Feature/Api/AdminProductApiTest.php tests/Feature/Orders/OrderServiceTest.php` passed, 31 tests / 291 assertions.
- 2026-05-03: `php artisan test --compact` passed, 137 tests / 790 assertions.
- 2026-05-03: `php artisan route:list --path=api/admin/v1 --except-vendor` passed and showed 39 admin API routes, including platform organizations/stores, store invites, and current membership.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Admin Platform API changes.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminPlatformApiTest.php` passed, 4 tests / 29 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/AdminPlatformApiTest.php tests/Feature/Api/AdminExportApiTest.php tests/Feature/Api/AdminThemeApiTest.php tests/Feature/Api/AdminContentSearchApiTest.php tests/Feature/Api/AdminSettingsApiTest.php tests/Feature/Api/AdminCollectionDiscountApiTest.php tests/Feature/Api/AdminProductApiTest.php tests/Feature/Api/AdminOrderApiTest.php tests/Feature/Analytics/AnalyticsTest.php tests/Feature/Developers/DeveloperIntegrationsTest.php` passed, 34 tests / 329 assertions.
- 2026-05-03: `php artisan test --compact` passed, 141 tests / 819 assertions.
- 2026-05-03: `php artisan route:list --name=password` passed and showed Fortify admin reset routes plus storefront customer reset routes.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after customer password reset changes.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/CustomerPasswordResetTest.php` passed, 4 tests / 20 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Auth/PasswordResetTest.php` passed, 4 tests / 8 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/CustomerAccountTest.php` passed, 6 tests / 26 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront tests/Feature/Auth/PasswordResetTest.php` passed, 23 tests / 106 assertions.
- 2026-05-03: `php artisan test --compact` passed, 145 tests / 839 assertions.
- 2026-05-03: `npm run build` passed for the updated customer account reset UI assets.
- 2026-05-03: Playwright smoke verified `/forgot-password`, customer reset-link success, `/reset-password/{token}` with email prefill, successful reset redirect to `/account/login`, post-reset customer login to `/account`, token deletion, and no current browser console warnings or errors at `http://shop.test`.
- 2026-05-03: `php artisan test --compact tests/Feature/Products/ProductServiceTest.php` passed, 7 tests / 20 assertions, including order-line product deletion/status guards and variant orphan archiving.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after product lifecycle guard regression tests.
- 2026-05-03: `php artisan test --compact tests/Feature/Products` passed, 12 tests / 35 assertions.
- 2026-05-03: `php artisan test --compact` passed, 148 tests / 844 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Products/MediaProcessingTest.php` passed, 4 tests / 59 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after media processing changes.
- 2026-05-03: `php artisan test --compact tests/Feature/Products` passed, 16 tests / 94 assertions.
- 2026-05-03: `php artisan test --compact` passed, 152 tests / 903 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Cart/CartServiceTest.php tests/Feature/Checkout/CheckoutServiceTest.php tests/Feature/Api/StorefrontCartApiTest.php tests/Feature/Api/StorefrontCheckoutPaymentApiTest.php tests/Feature/Storefront/StorefrontCartLivewireTest.php tests/Feature/Storefront/CheckoutStepFlowTest.php` passed, 18 tests / 127 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed with the cart discount-code migration.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after cart discount changes.
- 2026-05-03: `php artisan test --compact` passed, 156 tests / 932 assertions.
- 2026-05-03: `npm run build` passed for the updated cart drawer/cart page discount UI.
- 2026-05-03: Playwright smoke verified product add-to-cart, drawer discount apply/remove, cart-page discount apply, and checkout inheritance of `WELCOME10` at `http://shop.test`; current Playwright console check reported no warnings or errors.
- 2026-05-03: `php artisan test --compact tests/Feature/Admin/AdminPanelTest.php tests/Feature/Products/ProductServiceTest.php` passed, 16 tests / 111 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Admin/AdminPanelTest.php tests/Feature/Products tests/Feature/Api/AdminProductApiTest.php` passed, 29 tests / 228 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after admin variant-builder changes.
- 2026-05-03: `php artisan test --compact` passed, 158 tests / 945 assertions.
- 2026-05-03: `npm run build` passed for the updated admin product variant-builder UI.
- 2026-05-03: Playwright smoke verified `/admin/products/create` option/value rows, generated 2x2 variant table, per-variant SKU/price/stock edits, save redirect to `/admin/products/{id}/edit`, and no current Playwright console warnings or errors.
- 2026-05-03: `php artisan test --compact tests/Feature/Admin/AdminPanelTest.php tests/Feature/Storefront/ThemeNavigationTest.php tests/Feature/Storefront/StorefrontRenderTest.php tests/Feature/Tenancy/TenantResolutionTest.php tests/Feature/Api/AdminThemeApiTest.php` passed, 23 tests / 155 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after storefront section ordering, theme editor, and error template changes.
- 2026-05-03: `php artisan test --compact` passed, 161 tests / 964 assertions.
- 2026-05-03: `npm run build` passed for the updated storefront sections, error templates, and admin theme editor UI.
- 2026-05-03: Playwright smoke verified `http://shop.test/` homepage section rendering, `/products/missing-product` 404 search/home actions, suspended-store 503 identity/copy after temporary store status restoration, and `/admin/themes/1/editor` typed section editor with preview and rich-text settings. Current admin editor console check reported no warnings or errors; Boost browser logs only contained old 13:59 entries.
- 2026-05-03: `php artisan test --compact tests/Feature/Admin/AdminPanelTest.php tests/Feature/Orders/OrderServiceTest.php tests/Feature/Api/AdminOrderApiTest.php` passed, 24 tests / 201 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after admin order line fulfillment/refund changes.
- 2026-05-03: `php artisan test --compact` passed, 164 tests / 977 assertions.
- 2026-05-03: `npm run build` passed for the updated admin order-detail UI.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed before and after the admin order-detail browser smoke, leaving the local database in the seeded demo state.
- 2026-05-03: Playwright smoke verified `/admin/orders/1` selected line fulfillment quantity, tracking fields, computed selected-line refund amount, selected restock refund processing, and no current Playwright console warnings or errors. Boost browser logs only contained old 13:59 entries.

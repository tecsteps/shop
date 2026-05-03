# Shop Implementation Progress

Last updated: 2026-05-03

## Objective

Build the complete self-contained shop platform from `specs/*` and verify it with Pest plus browser smoke flows at `http://shop.test/`.

## Current State

- Repository started from the Laravel Livewire starter kit with Fortify authentication.
- Phase 1 foundation is implemented and committed: configuration defaults, core tenancy schema, models, factories, seeders, tenant middleware, customer guard provider registration, store role helper, and password_hash compatibility.
- Phase 2 catalog backend is implemented and committed: products, options, option values, variants, inventory, collections, collection pivot, media schema/models/factories/seed data, product lifecycle service, variant matrix service, inventory service, handle generator, and product/collection policies.
- Phase 3 theme/storefront shell is implemented and verified: theme/page/navigation schema, models, factories, seed data, theme settings service, navigation service, storefront layout, product cards, price rendering, and initial Livewire storefront pages.
- Phase 4 cart/checkout/pricing is implemented and verified: carts, cart lines, checkouts, shipping zones/rates, tax settings, discounts, cart and checkout services, pricing snapshots, storefront REST endpoints, Livewire cart/checkout UI, and cleanup jobs.
- Phase 5 payments/orders/customer persistence is implemented and verified: customers, customer addresses, orders, order lines, payments, refunds, fulfillments, mock PSP, checkout pay endpoint/UI, order confirmation, bank-transfer confirmation/cancellation services, and focused tests.
- Phase 6 customer accounts are implemented and verified: store-scoped customer login/registration, account dashboard, order history/detail pages, address book CRUD, and seeded customer credentials.
- Admin shop UI, search indexing, analytics, apps, and webhooks are not implemented yet.

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
7. **Admin panel** - Pending
   - Add admin shell, dashboard, resource management pages, settings, themes, pages, navigation, analytics, apps, and developers surfaces.
   - Verify admin login, store switching, product/order/discount/settings flows.
8. **Search, analytics, apps, webhooks** - Pending
   - Add SQLite FTS5 search, analytics ingestion/aggregation, API token support, app installs, webhook dispatch/signing/delivery.
   - Verify API, search, analytics, and webhook tests.
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

## Open Gaps

- Phase 1 still needs the resource policies listed in the roadmap, but most referenced resources do not exist until later phases. Policies will be added with their models to keep type hints and tests coherent.
- Phase 2 still needs Livewire/admin product management and full media resizing variants. Storefront product/collection browsing is covered by the Phase 3 shell.
- Phase 3 still needs the richer theme editor/admin surfaces, search modal autocomplete, checkout/account/error storefront templates, and fully configurable section ordering. These are deferred to the admin, search, checkout, and account slices.
- Phase 5 includes backend bank-transfer confirmation/cancellation, refunds, and fulfillment services. Admin UI actions for those services are deferred to the admin panel slice.
- Phase 4 has a functional cart page and accessible cart count, but the richer slide-out cart drawer can be expanded during UI polish.
- Discounts, shipping, and tax are implemented for the specified local/manual flows; provider/carrier integrations remain stubs by design.
- Order-reference guards in product deletion/status logic are present but only become fully meaningful once `order_lines` exists in Phase 5.
- Customer account password reset UI and emails remain deferred; login, registration, dashboard, order history/detail, and address book flows are implemented.
- Admin surfaces and all later shop phases remain unimplemented.
- API token requirements mention Sanctum, but the package is not currently installed. This remains an open dependency decision for the API/developers phase because dependencies must not be changed without approval.

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
- Pending: Playwright admin browser flows.

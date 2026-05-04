# Shop Build Progress

## Objective

Build a complete, self-contained Laravel shop system from `specs/*`, with implemented acceptance criteria mapped to evidence, passing automated checks, verified browser flows, independent QA, and meaningful commits.

## Current Status

- Status: in progress
- Active slice: Phase 6 - admin discounts/content/settings surfaces
- Started from: Laravel Livewire/Fortify starter kit with no shop domain tables or models present
- Last updated: 2026-05-04

## Phased Plan

1. Foundation: SQLite config, tenancy tables/models/factories/seeders, store resolution middleware, store scoping, admin/customer auth foundations, policies, and Phase 1 tests.
2. Catalog: product, variant, inventory, collection, media schema/models/services, admin catalog CRUD, storefront browsing, and tests.
3. Storefront theme/layout: theme/page/navigation data, storefront layout, product/collection/search/page views, and smoke browser verification.
4. Cart/checkout/pricing: cart API/UI, discount, shipping, tax, checkout state machine, and tests.
5. Payments/orders/fulfillment: mock PSP, order creation, refunds, fulfillments, customer account order views, and tests.
6. Admin panel: dashboard and all admin list/form/detail pages from Spec 03, with Livewire/Flux interactions.
7. Search/analytics/apps/webhooks: SQLite search, event aggregation, developer token/webhook UI, delivery jobs, and tests.
8. Final verification: full Pest suite, Pint, frontend build, Playwright MCP customer/admin flows, independent QA, fixes, and completion audit.

## Acceptance Checklist

| Area | Criteria Source | Status | Evidence |
| --- | --- | --- | --- |
| Database schema | `specs/01-DATABASE-SCHEMA.md` | partial | Phase 1 tenancy/auth tables, Phase 2 catalog tables, Phase 3 theme/content/navigation tables, Phase 4 cart/checkout/pricing tables, and Phase 5 order/payment/fulfillment tables are implemented: customer_addresses, orders, order_lines, payments, refunds, fulfillments, fulfillment_lines. Search/analytics/app tables are still missing. |
| Routes/API | `specs/02-API-ROUTES.md` | partial | Phase 1 auth routes plus catalog/cart/order/customer UI routes exist: `/`, `/collections`, `/collections/{handle}`, `/products/{handle}`, `/cart`, `/checkout`, `/checkout/confirmation/{order}`, `/search`, `/pages/{handle}`, `/account`, `/account/orders/{order}`, `/admin/products`, `/admin/products/create`, `/admin/products/{product}/edit`, `/admin/collections`, `/admin/collections/create`, `/admin/collections/{collection}/edit`, `/admin/inventory`, `/admin/orders`, `/admin/orders/{order}`, `/admin/customers`, `/admin/customers/{customer}`. Storefront REST API now includes cart line CRUD, checkout address/shipping/discount/payment-method selection, checkout payment completion, and token-gated order lookup under `/api/storefront/v1`. Admin REST API now includes store-scoped order list/detail, fulfillment creation, and refund creation under `/api/admin/v1/stores/{store}`. Storefront search/suggest, analytics, app/webhook APIs, and broader admin REST surfaces are still missing. |
| Admin UI | `specs/03-ADMIN-UI.md` | partial | Flux/Livewire admin catalog and operations shell now includes dashboard KPI/date-range reporting, orders-over-time bars, top-product summaries, funnel counters, product index/form, collection index/form, inventory list, order index/detail, order search/status filters, bank-transfer payment confirmation, refund creation, fulfillment creation, shipment status transitions, customer index/detail, customer order history, customer address create/edit/delete/default actions, and order-to-customer links with auth protection and store scoping. Discount/content/settings admin pages are still missing. |
| Storefront UI | `specs/04-STOREFRONT-UI.md` | partial | Storefront layout, home, collections index/detail, product detail, search, breadcrumbs, price display, product cards, DB-backed pages, seeded navigation menus, cached theme settings, product add-to-cart persistence, cart drawer, cart page with discount and shipping estimates, checkout address/shipping/discount/payment-selection/order-submission UI, order confirmation, and customer order list/detail views render seeded and runtime data. |
| Business logic | `specs/05-BUSINESS-LOGIC.md` | partial | `ResolveStore`, `BelongsToStore`, `StoreScope`, role checks, customer guard provider, catalog product lifecycle transitions, SKU uniqueness, variant matrix rebuilding, inventory reserve/release/commit/restock, automatic variant inventory, variant option mapping, media resize/cleanup job, `CartService`, `DiscountService`, `ShippingCalculator`, `TaxCalculator`, `PricingEngine`, checkout transitions through completion, checkout expiration, abandoned cart cleanup, session cart lookup, cart discount handoff to checkout, customer login cart merge, product add-to-cart mutations, `PaymentService`, mock PSP, idempotent order creation, checkout UI/API order submission, session/customer/token-gated order confirmation and lookup, customer order-history scoping, admin dashboard order aggregates, admin customer address management, admin order UI/API action orchestration, `RefundService`, `FulfillmentService`, bank-transfer payment confirmation, and unpaid bank-transfer cancellation are implemented. |
| Auth/security | `specs/06-AUTH-AND-SECURITY.md` | partial | Admin Livewire login/logout, customer guard/provider, customer login/register, login rate limiting, session hardening, and resource policies implemented. Customer password reset and Sanctum token auth still missing. |
| Seed/test data | `specs/07-SEEDERS-AND-TEST-DATA.md` | partial | Seeders create Acme Fashion and Acme Electronics stores/domains/settings/admin access, 6 collections, 25 products, 127 variants, 127 inventory rows, 206 variant option pivots, 2 themes, 6 theme files, 2 theme settings rows, 6 pages, 4 navigation menus, 17 navigation items, 2 tax settings rows, 2 shipping zones, 6 shipping rates, 6 discounts, and no product media/runtime carts. Order/payment seed data is still missing. |
| Browser E2E plan | `specs/08-PLAYWRIGHT-E2E-PLAN.md` | partial | Manual Playwright MCP smoke coverage has verified storefront catalog browsing, product detail, add-to-cart, cart drawer/page, checkout through order completion, order confirmation, customer order list/detail, search, DB-backed content pages, seeded navigation, admin dashboard on desktop/mobile, admin product/collection/inventory/order/customer pages, admin customer address creation, admin fulfillment creation/shipping/delivery, and auth flows without console warnings/errors on successful pages. Automated browser tests are still missing. |
| Roadmap phases | `specs/09-IMPLEMENTATION-ROADMAP.md` | in progress | Phase 1 foundation, Phase 2 catalog data/UI, Phase 3 theme/content/navigation data with storefront consumption, Phase 4 cart/checkout/pricing backend foundation, Phase 4 storefront cart/checkout UI through `payment_selected`, the Phase 4 cart/checkout REST API surface, cart-page estimates, Phase 5 order/payment backend foundation, Phase 5 refund/fulfillment services, Phase 5 storefront order completion/customer order surfaces, Phase 5 admin order management, Phase 5 order API surfaces, Phase 6 admin dashboard, and Phase 6 admin customer management are implemented. Phase 6 discount/content/settings admin surfaces are next. |

## Verification Evidence

- 2026-05-03: `mcp__laravel_boost__.application_info` confirmed PHP 8.4, Laravel 12.51.0, Livewire 4.1.4, Flux 2.12.0, Pest 4.3.2, SQLite.
- 2026-05-03: `mcp__laravel_boost__.database_schema(summary: true)` returned no tables for the current configured database.
- 2026-05-03: `git status --short` showed no current worktree changes before implementation.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Laravel 12 middleware/auth/authorization/migration docs plus Livewire 4 and Pest 4 docs before code changes.
- 2026-05-03: Planning challenger sub-agent found the repo was still a starter kit and produced the acceptance checklist by vertical slice. Risks added here: missing Sanctum dependency, password_hash/Fortify coordination, route/API absence, and E2E count inconsistencies.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed on real `database/database.sqlite` after creating the missing SQLite file.
- 2026-05-03: `php artisan test --compact` passed: 45 tests, 121 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after formatting changes.
- 2026-05-03: `php artisan route:list --path=admin` showed `/admin`, `/admin/login`, `/admin/logout`; `php artisan route:list --path=account` showed `/account`, `/account/login`, `/account/register`.
- 2026-05-03: Boost schema summary confirmed Phase 1 tables: organizations, stores, store_domains, store_users, store_settings, customers, customer_password_reset_tokens, users with password_hash.
- 2026-05-03: Playwright MCP verified `http://shop.test/` loads, `http://shop.test/admin/login` renders, admin login with `admin@acme.test` / `password` reaches `/admin`, `http://shop.test/account/login` renders with no console warnings/errors after the Livewire layout fix, and customer login with `customer@acme.test` / `password` reaches `/account`.
- 2026-05-03: Independent Phase 2 QA agents reported no critical findings. High findings on lifecycle bypass, child tenant scoping, inventory store mismatch, variant option mapping, simplified seed data, and media status-only processing were addressed in the catalog data layer.
- 2026-05-03: `php artisan test --compact tests/Feature/Catalog` passed: 19 tests, 71 assertions.
- 2026-05-03: `php artisan test --compact` passed: 64 tests, 192 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` fixed import/order/spacing, followed by passing catalog and full Pest suites.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after the Phase 2 seed update.
- 2026-05-03: Boost schema summary confirmed Phase 2 catalog tables. Boost query counts after fresh seed: stores 2, products 25, variants 127, inventory_items 127, collections 6, product_media 0, variant_option_values 206.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Livewire 4 full-page component routing/testing docs and Flux UI form/input/select/checkbox/button docs before catalog UI changes.
- 2026-05-03: `php artisan route:list --path=admin` showed 10 admin routes including product, collection, and inventory catalog pages. `php artisan route:list --path=collections`, `--path=products`, `--path=search`, and `--path=pages` showed storefront catalog/content routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Catalog/CatalogUiTest.php` passed: 4 tests, 44 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Catalog` passed: 23 tests, 115 assertions.
- 2026-05-03: `php artisan test --compact` passed: 68 tests, 236 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after formatting catalog UI changes.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after catalog UI test changes.
- 2026-05-03: `npm run build` passed with Vite production assets generated under ignored `public/build`.
- 2026-05-03: Playwright MCP verified `http://shop.test/`, `/collections/t-shirts`, `/products/classic-cotton-t-shirt`, `/search?q=Cotton`, `/pages/about`, `/admin/products`, `/admin/products/create`, `/admin/collections`, and `/admin/inventory` render with no console warnings/errors.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Laravel 12 migration/model/factory/seeder/cache docs and Pest 4 database testing docs before Phase 3 data-layer changes.
- 2026-05-03: Phase 3 explorer QA confirmed the required theme/page/navigation migrations, models, enums, factories, seeders, services, and tests; noted the spec tree-navigation mismatch because `navigation_items` has no `parent_id`.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after adding Phase 3 migrations and seeders.
- 2026-05-03: Boost schema summaries confirmed Phase 3 tables: themes, theme_files, theme_settings, pages, navigation_menus, navigation_items. Boost query counts after fresh seed: themes 2, theme_files 6, theme_settings 2, pages 6, navigation_menus 4, navigation_items 17.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/ThemeDataTest.php` passed: 6 tests, 31 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront tests/Feature/Catalog` passed: 29 tests, 146 assertions.
- 2026-05-03: `php artisan test --compact` passed: 74 tests, 267 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Phase 3 changes.
- 2026-05-03: `npm run build` passed after Phase 3 layout changes.
- 2026-05-03: Playwright MCP verified `http://shop.test/` renders seeded main navigation and `http://shop.test/pages/faq` renders DB-backed page content with no console warnings/errors.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Laravel 12 migration/job/scheduling/transaction docs and Pest 4 database testing docs before Phase 4 backend changes.
- 2026-05-03: Phase 4 explorer QA mapped the cart, checkout, discount, shipping, tax, pricing, and cleanup-job boundary; order creation/payment capture were kept for Phase 5 because order/payment tables are not implemented yet.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after adding Phase 4 migrations and seeders.
- 2026-05-03: Boost schema summary confirmed Phase 4 tables: carts, cart_lines, checkouts, shipping_zones, shipping_rates, tax_settings, discounts. Boost query counts after fresh seed: carts 0, cart_lines 0, checkouts 0, shipping_zones 2, shipping_rates 6, tax_settings 2, discounts 6.
- 2026-05-03: `php artisan test --compact tests/Feature/Cart tests/Feature/Checkout` passed: 11 tests, 55 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` fixed checkout/pricing style and import order, then passed after job test coverage was added.
- 2026-05-03: `php artisan test --compact` passed: 85 tests, 322 assertions.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Livewire 4 full-page components, forms, validation, session, events, redirects, Flux modal/form controls, and Pest docs before Phase 4 cart/checkout UI changes.
- 2026-05-03: `php artisan route:list --path=cart` and `php artisan route:list --path=checkout` confirmed `/cart` and `/checkout` Livewire routes.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront/CartCheckoutUiTest.php` passed: 4 tests, 19 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Storefront tests/Feature/Cart tests/Feature/Checkout tests/Feature/Catalog tests/Feature/Foundation/CustomerAuthTest.php` passed: 47 tests, 236 assertions.
- 2026-05-03: `vendor/bin/pint --dirty --format agent` passed after Phase 4 UI changes.
- 2026-05-03: `php artisan test --compact` passed: 89 tests, 341 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after the Phase 4 UI changes.
- 2026-05-03: `npm run build` passed after the cart/checkout Blade changes.
- 2026-05-03: Playwright MCP verified `http://shop.test/products/classic-cotton-t-shirt` add-to-cart, `http://shop.test/cart`, and `http://shop.test/checkout` render and progress through shipping, discount, and reserve-items payment selection with Livewire update requests returning 200 and no new console warnings/errors. Missing favicon links were fixed with `public/favicon.svg`.
- 2026-05-03: `mcp__laravel_boost__.search_docs` consulted Laravel 12 JSON API tests/resources/form requests/route model binding docs and Pest 4 JSON expectation docs before the Phase 4 API changes.
- 2026-05-03: `php artisan route:list --path=api/storefront/v1` confirmed 12 storefront API routes for carts, cart lines, checkouts, checkout address, shipping method, discount apply/remove, and payment method selection.
- 2026-05-03: `php artisan test --compact tests/Feature/Api/StorefrontCartApiTest.php tests/Feature/Api/StorefrontCheckoutApiTest.php` passed: 6 tests, 73 assertions.
- 2026-05-03: `php artisan test --compact tests/Feature/Api tests/Feature/Cart tests/Feature/Checkout tests/Feature/Storefront` passed: 27 tests, 178 assertions.
- 2026-05-03: `php artisan test --compact` passed after the cart/checkout API changes: 95 tests, 414 assertions.
- 2026-05-03: `php artisan migrate:fresh --seed --no-interaction` passed after the cart/checkout API changes.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Livewire 4 form/computed-property validation docs, Flux input/select/button docs, Laravel validation docs, and Pest docs before the cart estimate UI changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Storefront/CartCheckoutUiTest.php` passed after the cart estimate UI changes: 5 tests, 30 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Storefront tests/Feature/Cart tests/Feature/Checkout tests/Feature/Api` passed after the cart estimate UI changes: 28 tests, 189 assertions.
- 2026-05-04: `npm run build` passed after the cart estimate Blade changes.
- 2026-05-04: Playwright MCP verified `http://shop.test/products/classic-cotton-t-shirt` add-to-cart, `http://shop.test/cart` discount application and shipping rates, and checkout handoff with discounted totals at `http://shop.test/checkout`; Livewire requests returned 200 and browser console had no warnings/errors.
- 2026-05-04: `php artisan test --compact` passed after the cart estimate UI changes: 96 tests, 425 assertions.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after the cart estimate UI changes.
- 2026-05-04: `mcp__laravel_boost__.application_info` confirmed Laravel 12.51.0, PHP 8.4, Livewire 4.1.4, Flux 2.12.0, Pest 4.3.2, and SQLite before Phase 5 order/payment changes.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Laravel 12 transaction, enum/encrypted cast, and Pest database testing docs before Phase 5 order/payment changes.
- 2026-05-04: `vendor/bin/pint --dirty --format agent` passed after the Phase 5 order/payment backend foundation changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Payments tests/Feature/Orders` passed after adding mock PSP and order completion coverage: 11 tests, 64 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Cart tests/Feature/Checkout tests/Feature/Payments tests/Feature/Orders` passed after the order completion changes: 22 tests, 119 assertions.
- 2026-05-04: `php artisan test --compact` passed after the Phase 5 order/payment backend foundation: 107 tests, 489 assertions.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after adding Phase 5 order/payment/fulfillment migrations and factories.
- 2026-05-04: Boost schema summaries confirmed Phase 5 runtime tables: orders, order_lines, payments, refunds, fulfillments, and fulfillment_lines.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Laravel 12 job/scheduling, transaction, event, relationship aggregate, and Pest testing docs before refund/fulfillment service changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Orders` passed after adding refund, fulfillment, and bank-transfer service coverage: 15 tests, 86 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Cart tests/Feature/Checkout tests/Feature/Payments tests/Feature/Orders` passed after refund/fulfillment changes: 32 tests, 167 assertions.
- 2026-05-04: `php artisan test --compact` passed after refund/fulfillment changes: 117 tests, 537 assertions.
- 2026-05-04: `php artisan schedule:list` confirmed `CancelUnpaidBankTransferOrders` is scheduled daily alongside checkout/cart cleanup jobs.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after refund/fulfillment service changes.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Livewire 4 redirects/forms/testing docs, Flux UI form/button/input docs, Laravel validation/redirect docs, and Pest docs before storefront order-completion UI changes.
- 2026-05-04: `vendor/bin/pint --dirty --format agent` passed after storefront order-completion UI changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Storefront/OrderViewsTest.php` passed: 4 tests, 23 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Storefront tests/Feature/Cart tests/Feature/Checkout tests/Feature/Orders` passed after storefront order-completion changes: 41 tests, 225 assertions.
- 2026-05-04: `php artisan test --compact` passed after storefront order-completion changes: 121 tests, 560 assertions.
- 2026-05-04: `npm run build` passed after checkout confirmation and customer account Blade changes.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after storefront order-completion changes.
- 2026-05-04: `mcp__laravel_boost__.get_absolute_url(path: "/products/classic-cotton-t-shirt")` resolved `http://shop.test/products/classic-cotton-t-shirt` before browser verification.
- 2026-05-04: Playwright MCP verified product add-to-cart through checkout order completion, `http://shop.test/checkout/confirmation/1`, `http://shop.test/account`, and `http://shop.test/account/orders/1`; order number, line item, totals, payment/fulfillment badges, and account order history rendered with no console warnings/errors.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Livewire 4 full-page components/actions/security/testing docs, Flux UI button/modal/form docs, Laravel authorization docs, and Pest docs before admin order-management UI changes.
- 2026-05-04: `php artisan make:livewire Admin/Orders/Index --class --no-interaction`, `php artisan make:livewire Admin/Orders/Show --class --no-interaction`, and `php artisan make:test --pest Admin/OrderManagementTest --no-interaction` created the admin order slice files.
- 2026-05-04: `vendor/bin/pint --dirty --format agent` passed after admin order-management changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Admin/OrderManagementTest.php` passed: 5 tests, 30 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Admin tests/Feature/Orders tests/Feature/Catalog/CatalogUiTest.php` passed after admin order-management changes: 24 tests, 160 assertions.
- 2026-05-04: `php artisan route:list --path=admin/orders` confirmed `admin.orders.index` and `admin.orders.show` Livewire routes.
- 2026-05-04: `php artisan test --compact` passed after admin order-management changes: 126 tests, 590 assertions.
- 2026-05-04: `npm run build` passed after admin order Blade/sidebar changes.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after admin order-management changes.
- 2026-05-04: `mcp__laravel_boost__.get_absolute_url(path: "/admin/orders")` resolved `http://shop.test/admin/orders` before browser verification.
- 2026-05-04: Playwright MCP verified a seeded storefront checkout order appears in `http://shop.test/admin/orders`, `http://shop.test/admin/orders/1` shows line items/payments/totals/refund and fulfillment controls, and admin fulfillment creation, mark shipped, and mark delivered work with no current console warnings/errors. `browser_logs` only returned older May 3 auth-page warnings from a previously fixed issue.
- 2026-05-04: `mcp__laravel_boost__.application_info` reconfirmed Laravel 12.51.0, PHP 8.4, Livewire 4.1.4, Flux 2.12.0, Pest 4.3.2, and SQLite before the order API slice.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Laravel 12 JSON API resources, form requests, validation, route model binding, auth/rate limiting, and Pest JSON testing docs before the order API changes.
- 2026-05-04: `php artisan make:controller`, `php artisan make:request`, `php artisan make:resource`, `php artisan make:class`, and `php artisan make:test --pest` created the storefront/admin order API controllers, requests, resources, support token class, and tests.
- 2026-05-04: `vendor/bin/pint --dirty --format agent` passed after the order API changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Api/StorefrontOrderApiTest.php tests/Feature/Api/AdminOrderApiTest.php` passed: 5 tests, 38 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Api tests/Feature/Cart tests/Feature/Checkout tests/Feature/Orders tests/Feature/Payments` passed after the order API changes: 43 tests, 278 assertions.
- 2026-05-04: `php artisan route:list --path=api/storefront/v1 --except-vendor` showed 14 storefront API routes including checkout payment completion and token-gated order lookup.
- 2026-05-04: `php artisan route:list --path=api/admin/v1 --except-vendor` showed 4 admin order API routes for order list/detail, fulfillment creation, and refund creation.
- 2026-05-04: `php artisan test --compact` passed after the order API changes: 131 tests, 628 assertions.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after the order API changes.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Livewire 4 full-page/form/testing docs, Flux UI input/select/button/badge docs, Laravel aggregate/query docs, and Pest docs before the admin dashboard changes.
- 2026-05-04: `php artisan make:livewire Admin/Dashboard --class --no-interaction` and `php artisan make:test --pest Admin/DashboardTest --no-interaction` created the admin dashboard slice files.
- 2026-05-04: `vendor/bin/pint --dirty --format agent` passed after the admin dashboard changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Admin/DashboardTest.php` passed: 2 tests, 13 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Admin` passed after the admin dashboard changes: 7 tests, 43 assertions.
- 2026-05-04: `php artisan route:list --name=admin.dashboard` confirmed `/admin` is now the Livewire admin dashboard route.
- 2026-05-04: `npm run build` passed after the admin dashboard Blade changes.
- 2026-05-04: `php artisan test --compact` passed after the admin dashboard changes: 133 tests, 641 assertions.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after the admin dashboard changes.
- 2026-05-04: `mcp__laravel_boost__.get_absolute_url(path: "/admin")` resolved `http://shop.test/admin` before browser verification.
- 2026-05-04: Playwright MCP verified `http://shop.test/admin` renders the dashboard on desktop and 390px mobile viewports with KPI cards, chart panel, top-products panel, and funnel counters; current Playwright console checks reported no warnings/errors. Boost `browser_logs` only returned older May 3 account-login warnings from a previously fixed issue.
- 2026-05-04: `mcp__laravel_boost__.search_docs` consulted Livewire 4 full-page/pagination/form/testing docs, Flux UI input/select/button/badge/modal/table docs, Laravel relationship aggregate docs, and Pest docs before the admin customer-management changes.
- 2026-05-04: `php artisan make:livewire Admin/Customers/Index --class --no-interaction`, `php artisan make:livewire Admin/Customers/Show --class --no-interaction`, and `php artisan make:test --pest Admin/CustomerManagementTest --no-interaction` created the admin customer slice files.
- 2026-05-04: `vendor/bin/pint --dirty --format agent` passed after the admin customer-management changes.
- 2026-05-04: `php artisan test --compact tests/Feature/Admin/CustomerManagementTest.php` passed: 4 tests, 26 assertions.
- 2026-05-04: `php artisan test --compact tests/Feature/Admin` passed after the admin customer-management changes: 11 tests, 69 assertions.
- 2026-05-04: `php artisan route:list --path=admin/customers` confirmed `admin.customers.index` and `admin.customers.show` Livewire routes.
- 2026-05-04: `npm run build` passed after the customer admin Blade/sidebar changes.
- 2026-05-04: `php artisan test --compact` passed after the admin customer-management changes: 137 tests, 667 assertions.
- 2026-05-04: `php artisan migrate:fresh --seed --no-interaction` passed after the admin customer-management changes.
- 2026-05-04: `mcp__laravel_boost__.get_absolute_url(path: "/admin/customers")` resolved `http://shop.test/admin/customers` before browser verification.
- 2026-05-04: Playwright MCP verified `http://shop.test/admin/customers` renders the customer table, seeded customer detail renders at `/admin/customers/1`, and the address modal creates a default address; current Playwright console checks reported no warnings/errors.

## Decisions

- Follow the roadmap order strictly. Phase 1 is required before catalog, checkout, admin, storefront, and QA flows can be meaningfully implemented.
- Preserve the starter Fortify conventions where they do not conflict with the shop specs.
- Use integer minor units for all money columns and JSON text columns cast through Eloquent as specified.
- Keep `password_hash` as the database column for admin users and customers while exposing `password`/`getAuthPassword()` at the model layer for Fortify/Laravel auth compatibility.
- Seed both `shop.test` and `acme-fashion.test` as storefront domains for the first store so the goal URL and E2E spec URL can both resolve.
- Customer Livewire auth components persist `storeId` from the initial storefront request because Livewire update requests do not run through the original storefront route middleware.
- The seed specification contains an arithmetic conflict: Acme Fashion's per-product variant table sums to 117 variants, while the prose says 107. The implementation follows the concrete per-product table, resulting in 117 Fashion variants and 10 Electronics variants, 127 total.
- Child catalog models without a `store_id` column are tenant-scoped through their parent product relationship. Inventory keeps its denormalized `store_id` and enforces that it matches the variant product store at save time.
- Storefront content pages now resolve from the `pages` table and only published pages are rendered.
- The catalog admin form intentionally blocks active products without a priced variant and duplicate in-store SKUs during UI edits, mirroring service-layer invariants where the current CRUD surface touches product variants directly.
- Navigation tree support is flat for now because the Phase 3 schema defines `navigation_items.position` but no `parent_id`; `NavigationService::buildTree()` returns a flat tree-compatible array with empty children.
- Cart, checkout, and pricing runtime records are not seeded; only deterministic tax, shipping, and discount configuration is seeded. Runtime carts/checkouts are created by services and tests.
- The cart drawer reads the current session/customer cart without creating an empty cart on every storefront render; carts are created on first add-to-cart or checkout/cart service mutation.
- The checkout UI reserves inventory by selecting a payment method, then submits payment through `CheckoutService::completeCheckout()` so failed card payments can release reservations and return customers to payment selection.
- Order confirmation access is allowed for the authenticated owning customer or for the session that just placed the order via `last_order_id`.
- Customer account order list/detail routes use the `customer` guard and reload orders through explicit store/customer constraints.
- The cart REST API exposes `cart_version` as the public optimistic concurrency field while the service layer keeps its `expectedVersion` argument; `expected_version` remains accepted as a compatibility alias in API requests.
- Cart page discount codes are validated with `DiscountService`, saved in session, and applied to the checkout when the customer proceeds.
- `orders.checkout_id` is intentionally added beyond the original schema table to enforce idempotent checkout completion without duplicate orders.
- Failed card payments release reserved inventory and move the checkout back to `shipping_selected` so customers can retry payment selection.
- Bank transfer order completion creates a pending order and payment while keeping inventory reserved until the later admin payment-confirmation flow.
- Inventory commit/release/restock locks by primary key without the current-store global scope because scheduled jobs can operate across stores in one run.
- Admin order Livewire components persist locked `storeId`/`orderId` state and reload orders without global scopes but with explicit store constraints before each service-backed action.
- Admin order actions deliberately delegate to `OrderService`, `RefundService`, and `FulfillmentService` so UI validation cannot bypass payment, refund, fulfillment, or inventory guards.
- Storefront order lookup uses a deterministic HMAC access token derived from store/order identity so confirmation links can be token-gated without adding a new runtime column.
- Admin order API routes currently use the existing session `auth` middleware and store-user membership checks because Sanctum is not installed yet.
- Admin dashboard sales and top-product metrics are derived from paid or partially refunded orders; visitor counts remain zero until the analytics event tables are implemented.
- Customer addresses are scoped through the locked customer id because `customer_addresses` intentionally belongs to customers and has no direct `store_id` column.

## Open Issues

- Composer does not currently include Sanctum, while Spec 06 requires Sanctum personal access tokens. This will need either an approved dependency addition or a documented compatible alternative before final completion.
- The Herd app URL in the goal is `http://shop.test/`, while the E2E spec also references `http://acme-fashion.test`; domain handling must support seeded store domains and Herd verification.
- Phase 1 still lacks admin/customer password reset at the spec paths and a custom customer password reset token repository that scopes by store_id.
- Policy classes for later resources use generic object parameters until the concrete catalog/order/content models exist.
- `php artisan route:list --except-vendor` hides Livewire full-page routes because their controller is vendor-provided; path-filtered route-list commands are used as evidence for those routes.
- Phase 2 media upload/admin UI is still missing, even though the media schema/job layer exists.
- Product options can be displayed and edited as text in the admin form, but full option matrix generation and option-value reassignment UI are not complete.
- Discount and theme/page/navigation admin management UI is still missing even though the relevant data layer, seeders, services, and storefront/checkout consumption exist.
- Storefront search/suggest, analytics event capture, app/webhook APIs, and broader admin REST endpoints outside order management are still missing.
- Automated browser tests from Spec 08 are still missing; current browser coverage is manual Playwright MCP smoke verification.
- SQLite enum/check constraints from the schema spec are not yet explicitly enforced as database `CHECK` constraints; enum validation is currently enforced through casts/services/model invariants.
- Product media processing now validates and resizes image uploads with GD and cleans predictable generated paths, but browser/admin upload UI is still pending.

## Completion Summary

Not complete. Phase 1 foundation, Phase 2 catalog data/UI surfaces, Phase 3 storefront theme/content/navigation data, the Phase 4 cart/checkout/pricing backend foundation, cart/checkout storefront UI through order completion, cart-page estimates, cart/checkout REST APIs, Phase 5 order/payment backend foundation, Phase 5 refund/fulfillment services, customer order views, admin dashboard/order/customer management, and order API surfaces are implemented, with known auth/token, media UI, discount/content/settings admin UI, search/analytics/app/webhook API, browser-test, and database CHECK constraint gaps tracked above.

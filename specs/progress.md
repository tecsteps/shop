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
- [x] Themes + CMS + Storefront Blade layouts

### Phase 4: Cart / Checkout / Discounts / Shipping / Taxes
- [x] customers, customer_addresses, carts, cart_lines, checkouts, shipping_zones, shipping_rates, tax_settings, discounts migrations + models + factories
- [x] Customer model (Authenticatable), CustomerUserProvider wired to the real model
- [x] CartService, DiscountService, ShippingCalculator, TaxCalculator, PricingEngine, CheckoutService
- [x] PricingResult / TaxLine / DiscountResult value objects
- [x] Storefront Livewire: Cart\Show, Cart\Drawer, Checkout\Show, Checkout\Success + routes /cart /checkout /checkout/success
- [x] Pest feature tests for cart/discount/shipping/tax/pricing/checkout services + cart Livewire page

### Phase 5: Payments / Orders / Fulfillment
- [x] orders, order_lines, payments, refunds, fulfillments, fulfillment_lines migrations + models + factories
- [x] Enums: OrderStatus, FinancialStatus, FulfillmentStatus, PaymentStatus, RefundStatus, FulfillmentShipmentStatus
- [x] PaymentProvider contract + MockPaymentProvider (magic cards: 4242... success, 4000...0002 decline, 4000...9995 insufficient_funds, 5555...4444 success)
- [x] OrderService, PaymentService, RefundService, FulfillmentService
- [x] Events: OrderCreated, OrderPaid, OrderCancelled, OrderRefunded, OrderFulfilled, FulfillmentCreated, FulfillmentShipped, FulfillmentDelivered
- [x] Checkout Livewire `place()` wires authorize -> createFromCheckout -> recordPayment -> redirect /checkout/success?order={order_number}
- [x] Admin orders list + detail placeholder Livewire pages at /admin/orders and /admin/orders/{order}
- [x] Pest tests: card success/decline/insufficient_funds, bank_transfer pending order, digital auto-fulfill, idempotent authorize, refund partial/full/restock/reject, fulfillment state machine and guard

### Phase 6: Customer Accounts
- [x] Customer auth Livewire (Login, Register, Logout, ForgotPassword, ResetPassword, SetPassword, EmailVerify)
- [x] Account area Livewire (Dashboard, Orders\Index, Orders\Show, Addresses CRUD, Profile)
- [x] Custom CustomerTokenRepository scoped by store_id; PasswordBrokerManager override routing the 'customers' broker through it
- [x] CustomerWelcomeNotification + CustomerResetPasswordNotification (customer guard implements CanResetPassword)
- [x] OrderService::createFromCheckout now resolves or creates a Customer by (store_id, email) and sends CustomerWelcomeNotification on guest creation
- [x] Routes: /account/login, /register, /logout, /forgot-password, /reset-password/{token}, /set-password, /email/verify/{id}/{hash} + auth:customer /account (dashboard), /account/orders, /account/orders/{orderNumber}, /account/addresses, /account/profile
- [x] Pest feature tests (tests/Feature/Account): registration (per-store email uniqueness, cross-store reuse), login (success/failure/rate limit), logout, forgot+reset full flow, guest checkout creates customer + welcome, set-password activates guest account, orders index scoped to customer, address CRUD, email verification

### Phase 7: Admin Panel
- [x] Admin layout shell (fixed sidebar, topbar with store switcher + profile dropdown, Flux-based, dark mode aware)
- [x] Dashboard (revenue today, orders today, AOV, visits today, recent 10 orders)
- [x] Products (index with search/status filter, create form, edit form with variants table)
- [x] Collections (index with search, create/edit form)
- [x] Orders (index with search + status filter, polished Show with fulfill/refund/cancel + bank-transfer confirm)
- [x] Customers (index with search + totals, show with order history and addresses)
- [x] Discounts (index, create/edit with type, value type, dates, usage limit, status)
- [x] Pages (index, create/edit with status workflow)
- [x] Themes (index grid + publish action)
- [x] Settings (general store settings, shipping zones list, taxes mode/provider, staff invite modal)
- [x] Store switcher writes session('current_store_id') and redirects to /admin
- [x] Routes registered under /admin with store.resolve:admin middleware and named admin.*
- [x] Authorization via existing policies in mount()/actions (Support = read-only, Staff = edit, Owner/Admin full)
- [x] OwnerUserSeeder seeds owner@shop.test (password: password) with Owner role on the default store
- [x] Pest tests at tests/Feature/Admin/ covering index + show + edit 200s and authorization denials

### Phase 8: Search
- [x] SQLite FTS5 + SearchService + storefront /search + search_queries logging

### Phase 9: Analytics
- [x] analytics_events + analytics_daily tables, AnalyticsService with client_event_id idempotency, DashboardMetricsService helper, analytics:rollup command (scheduled daily 01:00), storefront page_view/product_view hooks, OrderPaid -> checkout_completed listener

### Phase 10: Apps & Webhooks
- [x] apps, app_installations, webhook_subscriptions, webhook_deliveries migrations + models + factories (encrypted signing_secret)
- [x] WebhookTopic enum (order.created/paid/fulfilled/cancelled/refunded, fulfillment.created/shipped, customer.created, product.created/updated/deleted, checkout.completed)
- [x] WebhookDispatcher service scoped by store + event_type + active status
- [x] DeliverWebhook queued job (tries=8, backoff 30/60/120/300/900/3600/7200s) with HMAC-SHA256 signature, X-Shop-* headers, WebhookDelivery row per attempt, circuit breaker pausing subscription after 5 consecutive failures
- [x] CustomerCreated event + Customer observer; DispatchWebhooks listener mapping Order/Fulfillment/Customer events to topics via EventServiceProvider
- [x] Admin Livewire: Settings\Webhooks\Index, Edit, Deliveries + routes under /admin/settings/webhooks

### Phase 11: Polish
- [x] 11d: Admin Dashboard wired to DashboardMetricsService::forDay() (revenue, orders, AOV, visits, add-to-cart, checkout started, checkout completed)
- [x] 11d: Products\Index search delegates to SearchService::search() when query has >= 2 chars; falls back to paginated list otherwise
- [x] 11d: Admin sidebar adds a Webhooks link under System pointing to /admin/settings/webhooks
- [x] 11d: Admin layout gains an Alpine-driven toast container listening for the 'toast' window event; session flash status/error auto-dispatch on page load
- [x] 11d: Focus-visible rings on sidebar anchors; dark mode parity maintained
- [x] 11c: Error views (errors/404, 403, 500, 503) rendering inside the storefront layout when a store is resolved, else a minimal standalone fallback
- [x] 11c: Announcement bar driven by StoreSettings.settings_json['announcement'] with seeded fallback and explicit enabled flag
- [x] 11c: Storefront header swapped to use <livewire:storefront.cart.drawer />; Drawer expanded to an aria-modal dialog with items + subtotal + checkout CTA + wire:loading states
- [x] 11c: Skip-to-content link, role=main/contentinfo landmarks, aria-label on nav regions, aria-current on active home link, aria-live flash region
- [ ]

## Log
- 2026-04-17: Starting implementation with team mode.
- 2026-04-17: Phase 1 complete. Kept users.password column name (not renamed to password_hash) to preserve Fortify starter-kit tests; override not required. Added /admin and /account route groups alongside existing Fortify routes. Customer guard registered via CustomerUserProvider which falls back to User model until Phase 6 introduces Customer.
- 2026-04-17: Phase 3 complete. Added themes/theme_files/theme_settings/pages/navigation_menus/navigation_items migrations (CHECK triggers on status/type enums), Eloquent models with BelongsToStore trait where applicable, factories, DefaultStoreSeeder creating shop.test hostname, ThemeSeeder/PageSeeder/NavigationSeeder seeding default theme plus about/contact pages and main-menu. Added storefront Blade layout component at resources/views/components/layouts/storefront.blade.php with announcement bar, header (logo, navigation, cart, account links), main slot, footer. Created class-based Livewire components under App\Livewire\Storefront (Home, Collections\Show, Products\Show, Pages\Show, Navigation partial). Routes wired inside the storefront middleware group. Pages show renders 404 for non-published. Full test suite green (62 passed).
- 2026-04-17: Phase 7 complete. Added admin layout (components/layouts/admin.blade.php) with fixed Flux sidebar, topbar store switcher, profile menu; full set of Admin\* Livewire components (Dashboard, Products Index/Create/Edit, Collections Index/Edit, Orders Index/Show w/ fulfill+refund+cancel, Customers Index/Show, Discounts Index/Edit, Pages Index/Edit, Themes Index w/ publish, Settings General/Shipping/Taxes/Staff). Routes under /admin using Route::get('path', Component::class)->name(...) pattern with store.resolve:admin middleware; /admin/switch-store/{store} writes session('current_store_id'). Authorization via existing policies in mount()/action bodies. OwnerUserSeeder adds owner@shop.test/password linked to shop store as Owner. 20 new tests in tests/Feature/Admin covering index/show/edit 200 responses, authorization denials for Support/Staff, and the store switcher flow; full suite 190 passing.
- 2026-04-17: Phase 4 complete. Added 9 migrations and enums (CartStatus, CheckoutStatus, PaymentMethod, DiscountType, DiscountValueType, DiscountStatus, ShippingRateType, TaxMode, TaxProviderType). Customer model now extends Authenticatable, implements BelongsToStore, overrides getAuthPassword() to return password_hash; CustomerUserProvider references the real model. New services: CartService (optimistic cart_version locking, inventory-aware addLine/updateLineQuantity/removeLine/mergeOnLogin), DiscountService (case-insensitive code lookup, allocation with largest-remainder, status/usage/rules validation throwing InvalidDiscountException with reason codes), ShippingCalculator (zone matching by country + region with specificity tiebreak, flat/weight/price rate calculators, skip-for-digital-only cart), TaxCalculator (manual provider with inclusive extraction via intdiv and exclusive additive rates, shipping_taxable flag), PricingEngine (pipeline: subtotal -> discount -> shipping -> tax -> total, snapshots to totals_json), CheckoutService (state transitions with inventory reservation on payment selection). Added storefront cart page, cart drawer stub, checkout multi-section view, success page + routes. CommerceSeeder wired additively into DatabaseSeeder. 46 new Pest tests covering happy and failure paths; full suite 120 passing.
- 2026-04-17: Phase 11d complete. Admin Dashboard now pulls KPIs from App\Services\DashboardMetricsService::forDay() (keys: revenue_amount, orders_count, aov_amount, visits_count, add_to_cart_count, checkout_started_count, checkout_completed_count) with a new funnel tile below the KPIs. Admin\Products\Index search now delegates to App\Services\SearchService::search() for queries >= 2 chars (IDs are re-applied to the paginated Product query so filters still compose); shorter inputs fall back to the plain list. Added Webhooks link under the System group in resources/views/components/layouts/admin.blade.php pointing to /admin/settings/webhooks. Layout now renders a fixed top-right Alpine toast container listening on the window 'toast' event; session('status') and session('error') auto-dispatch toasts on page load so existing session flashes continue to work without edits. Added focus-visible ring utilities to sidebar anchors. Two new tests in tests/Feature/Admin/ProductsSearchTest verify the SearchService delegation and short-query fallback via mocked service. All 22 admin tests green; full suite 200 passing (1 unrelated Polish\AnnouncementBarTest failure owned by Phase 11c). Created minimal routes/api.php stub (Sanctum /user) to unblock the framework boot referenced by bootstrap/app.php; Phase 11a will replace with full API.
- 2026-04-17: Phase 6 complete. Customer is now CanResetPassword; 'customers' password broker overridden via a PasswordBrokerManager extension that uses CustomerTokenRepository (scopes tokens by (email, store_id) so the same email on two stores does not collide). New Livewire components under App\Livewire\Storefront\Account (Dashboard, Profile, Addresses, Orders\Index, Orders\Show) and App\Livewire\Storefront\Account\Auth (Login hardened with per-email rate limiting, Register, Logout, ForgotPassword, ResetPassword, SetPassword, EmailVerify). OrderService::createFromCheckout now attaches or creates a Customer by (store_id, email) and, when the customer was newly created guest, dispatches CustomerWelcomeNotification containing a reset link to /account/set-password?token=... which lets the guest set a password and auto-verifies their email. Routes added under the /account prefix inside the storefront middleware group. 16 new Pest feature tests in tests/Feature/Account/; full suite 170 passing.
- 2026-04-17: Phase 11c complete. Added resources/views/errors/{404,403,500,503}.blade.php which delegate to a shared errors/layout.blade.php; when app()->bound('current_store') the page renders inside x-layouts.storefront, otherwise a minimal standalone HTML body so unresolved-host responses still return a friendly page. Storefront layout rewritten: announcement bar is now backed by $currentStore->settings->settings_json['announcement'] (enabled/text/link) with a 'Free shipping on orders over $50' fallback; the Cart link was replaced by <livewire:storefront.cart.drawer />; added session-status aria-live flash region; sweeping focus-visible:ring utilities on nav/footer/drawer interactive elements; aria-current='page' on the home link when path is '/'. Cart\Drawer now toggles an aria-modal dialog panel listing cart lines, subtotal, a Checkout CTA, a View full cart link, plus wire:loading markers on toggle/refreshDrawer. 9 new Pest tests under tests/Feature/Polish/ covering homepage resolves at http://shop.test/, 404 + 503 error pages, minimal fallback for unknown host, skip-link and landmark assertions, aria-current on homepage, plus StoreSettings-driven announcement (configured/default/disabled). Full suite 201 passed.

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
- [ ]

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
- [ ]

### Phase 12: Full Test Suite (Pest + Playwright E2E)
- [ ]

## Log
- 2026-04-17: Starting implementation with team mode.
- 2026-04-17: Phase 1 complete. Kept users.password column name (not renamed to password_hash) to preserve Fortify starter-kit tests; override not required. Added /admin and /account route groups alongside existing Fortify routes. Customer guard registered via CustomerUserProvider which falls back to User model until Phase 6 introduces Customer.
- 2026-04-17: Phase 3 complete. Added themes/theme_files/theme_settings/pages/navigation_menus/navigation_items migrations (CHECK triggers on status/type enums), Eloquent models with BelongsToStore trait where applicable, factories, DefaultStoreSeeder creating shop.test hostname, ThemeSeeder/PageSeeder/NavigationSeeder seeding default theme plus about/contact pages and main-menu. Added storefront Blade layout component at resources/views/components/layouts/storefront.blade.php with announcement bar, header (logo, navigation, cart, account links), main slot, footer. Created class-based Livewire components under App\Livewire\Storefront (Home, Collections\Show, Products\Show, Pages\Show, Navigation partial). Routes wired inside the storefront middleware group. Pages show renders 404 for non-published. Full test suite green (62 passed).
- 2026-04-17: Phase 4 complete. Added 9 migrations and enums (CartStatus, CheckoutStatus, PaymentMethod, DiscountType, DiscountValueType, DiscountStatus, ShippingRateType, TaxMode, TaxProviderType). Customer model now extends Authenticatable, implements BelongsToStore, overrides getAuthPassword() to return password_hash; CustomerUserProvider references the real model. New services: CartService (optimistic cart_version locking, inventory-aware addLine/updateLineQuantity/removeLine/mergeOnLogin), DiscountService (case-insensitive code lookup, allocation with largest-remainder, status/usage/rules validation throwing InvalidDiscountException with reason codes), ShippingCalculator (zone matching by country + region with specificity tiebreak, flat/weight/price rate calculators, skip-for-digital-only cart), TaxCalculator (manual provider with inclusive extraction via intdiv and exclusive additive rates, shipping_taxable flag), PricingEngine (pipeline: subtotal -> discount -> shipping -> tax -> total, snapshots to totals_json), CheckoutService (state transitions with inventory reservation on payment selection). Added storefront cart page, cart drawer stub, checkout multi-section view, success page + routes. CommerceSeeder wired additively into DatabaseSeeder. 46 new Pest tests covering happy and failure paths; full suite 120 passing.
- 2026-04-17: Phase 6 complete. Customer is now CanResetPassword; 'customers' password broker overridden via a PasswordBrokerManager extension that uses CustomerTokenRepository (scopes tokens by (email, store_id) so the same email on two stores does not collide). New Livewire components under App\Livewire\Storefront\Account (Dashboard, Profile, Addresses, Orders\Index, Orders\Show) and App\Livewire\Storefront\Account\Auth (Login hardened with per-email rate limiting, Register, Logout, ForgotPassword, ResetPassword, SetPassword, EmailVerify). OrderService::createFromCheckout now attaches or creates a Customer by (store_id, email) and, when the customer was newly created guest, dispatches CustomerWelcomeNotification containing a reset link to /account/set-password?token=... which lets the guest set a password and auto-verifies their email. Routes added under the /account prefix inside the storefront middleware group. 16 new Pest feature tests in tests/Feature/Account/; full suite 170 passing.

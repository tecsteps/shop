# Implementation Progress

> Self-contained multi-tenant e-commerce platform. Built in team mode against `specs/*`.
> Local dev URL: `http://shop.test` (Laravel Herd). Storefront resolves by hostname; admin/api by path + session.

## Status Legend
- [ ] not started
- [~] in progress
- [x] done + tested
- [B] blocked

## Phases

### Phase 1: Foundation (CRITICAL — blocks all) ✅ DONE
- [x] Config: auth guards (web/customer/sanctum-deferred), SQLite tuning (WAL, FK, busy_timeout), logging JSON channel
- [x] Core migrations: organizations, stores, store_domains, users mods (status/last_login_at/password_hash), store_users, store_settings, customers, customer_addresses
- [x] Core models + factories + seeders: Organization, Store, StoreDomain, StoreUser, StoreSettings, Customer, CustomerAddress
- [x] Enums: StoreStatus, StoreUserRole, StoreDomainType
- [x] Tenant middleware ResolveStore + storefront/admin/api middleware groups + aliases (store.resolve, role.check, auth.customer)
- [x] BelongsToStore trait + StoreScope global scope (auto-sets store_id)
- [x] Admin auth (Livewire Login/Logout, 5/min rate limit, last_login_at, session regen, generic error)
- [x] Customer auth (customer guard, CustomerUserProvider store-scoped, per-store email uniqueness)
- [x] Policies (Product/Order/Collection/Discount/Customer/Store/Page/Theme/Fulfillment/Refund) + role matrix + gates
- [x] Pest test helpers (createStoreContext, actingAsAdmin, actingAsCustomer, bindCurrentStore, storefrontUrl)
- [x] Route skeleton (storefront/admin/api files) + bootstrap wiring; DemoStoreSeeder hook
- VERIFIED: 31 passed / 1 skipped (cart-merge stub, Phase 4); pint clean; migrate:fresh --seed clean; shop.test storefront HTTP 200; admin login browser-verified.
- Demo creds: admin@shop.test / password (Owner); customer@shop.test / password. Storefront http://shop.test, admin http://shop.test/admin.

### Phase 2: Catalog ✅ DONE (catalog)
- [x] Migrations + models (Product, ProductOption, ProductOptionValue, ProductVariant, InventoryItem, Collection, ProductMedia) — 9 migrations, composite PKs, enum CHECKs
- [x] Enums (ProductStatus, VariantStatus, CollectionStatus, CollectionType, MediaType, MediaStatus, InventoryPolicy)
- [x] Services (ProductService state machine + delete guard, VariantMatrixService cartesian, InventoryService transactions, HandleGenerator store-scoped)
- [x] Media upload job (GD resize thumb/medium/large, status transitions) + Admin\Products\MediaManager Livewire
- [x] Tests: HandleGenerator(6) + Products/{ProductCrud(13),Variant(8),Inventory(8),Collection(7),MediaUpload(6)} = 48 passed; pint clean
- CatalogSeeder: 20 products / 72 variants / inventory / 3 collections (idempotent). Variant auto-creates inventory_item. order_lines guards activate once Phase 5 lands variant_id col.

### Phase 3: Themes, Pages, Navigation, Storefront Layout ✅ DONE (storefront)
- [x] Migrations + models (Theme, ThemeFile, ThemeSettings, Page, NavigationMenu, NavigationItem +parent_id) + factories
- [x] Enums (ThemeStatus, PageStatus, NavigationItemType); tightened Page/Theme policies
- [x] Storefront blade layout (skip link, announcement, sticky header + dropdowns, mobile drawer, footer, dark mode, ARIA) + components (x-storefront::price/product-card/badge/quantity-selector/address-form/order-summary/breadcrumbs/pagination) + errors/404+503
- [x] NavigationService (tree/buildTree/resolveUrl, 5min cache) + ThemeSettingsService singleton (all/get/forget, 5min cache)
- [x] Livewire Home/Collections.Index/Collections.Show/Pages.Show; routes storefront.home/collections.index/collections.show/pages.show
- VERIFIED: 117 passed / 1 skipped; pint clean; pages render at shop.test, 0 console errors. StorefrontSeeder (theme+menus+pages, idempotent). Hooks left for #6: open-cart-drawer/open-search-modal events, cart-count badge, $globals slot for CartDrawer+SearchModal.

### Phase 4: Cart, Checkout, Discounts, Shipping, Taxes ✅ DONE (commerce) — backend
- [x] Migrations + models (Cart, CartLine, Checkout, ShippingZone, ShippingRate, TaxSettings, Discount)
- [x] Enums (CartStatus, CheckoutStatus, DiscountType, DiscountValueType, DiscountStatus, ShippingRateType, TaxMode)
- [x] Services (CartService versioned, DiscountService reason-codes+largest-remainder, ShippingCalculator, TaxCalculator, PricingEngine snapshot, CheckoutService state machine)
- [x] Value objects (PricingResult, TaxLine, DiscountResult, PaymentResult) + jobs (ExpireAbandonedCheckouts 15min, CleanupAbandonedCarts daily) wired in console
- [~] Storefront cart/checkout UI — backend services + integration contract ready; UI built in #6
- [x] Tests (PricingEngine, DiscountCalculator, TaxCalculator, ShippingCalculator, CartVersion + Cart/Checkout feature)
- config/shop.php added (abandoned_cart_days, checkout_expiry_hours, bank_transfer_cancel_days, order_number_start/prefix)

### Phase 5: Payments, Orders, Fulfillment ✅ DONE (commerce)
- [x] Migrations + models (Customer/CustomerAddress extended, Order, OrderLine, Payment, Refund, Fulfillment, FulfillmentLine; orders.checkout_id for idempotency)
- [x] Enums (OrderStatus, FinancialStatus, FulfillmentStatus, PaymentMethod, PaymentStatus, RefundStatus, FulfillmentShipmentStatus)
- [x] Mock PSP (PaymentProvider contract + MockPaymentProvider via PaymentServiceProvider; magic cards), OrderService (createFromCheckout atomic, sequential #1001+), RefundService, FulfillmentService (guard + qty caps)
- [x] Events (OrderCreated/Paid/Fulfilled/Cancelled/Refunded + Checkout/Fulfillment events) + ConfirmBankTransferPayment action + CancelUnpaidBankTransferOrders job
- [x] Tests (MockPaymentProvider, PaymentService, BankTransferConfirmation, OrderCreation, Refund, Fulfillment)
- CommerceSeeder: shipping zones/rates, tax settings, discounts WELCOME10/SAVE5/FREESHIP, sample orders #1001-#1003 (paid/fulfilled/bank-transfer-pending)
- VERIFIED: full suite 266 passed / 0 failed; pint clean; migrate:fresh --seed clean (20 products, 72 variants, 3 orders, 3 discounts).
- Storefront routes #6 must add: /cart, /checkout, /checkout/confirmation/{orderId}. Admin #7: order confirm-payment/refund/fulfillment actions.

### Phase 3b/6: Storefront shopping UI + Customer Accounts ✅ DONE (storefront)
- [x] Products\Show (gallery, variant selector, sale price, stock messaging), CartDrawer (in layout), Cart\Show, Checkout\Show (stepper), Checkout\Confirmation
- [x] Account Dashboard, Orders Index/Show (404 on other customers), Addresses CRUD + default; mergeOnLogin on login
- [x] Routes: storefront.products.show/cart/checkout/checkout.confirmation/search + account.*; fixed Alpine->Livewire.dispatch event-bus bug
- [x] Tests (CustomerAccount 7, AddressManagement 8, ProductPage 5) green; full purchase flow browser-verified (Order #1004, totals 41.02 USD)

### Tenancy fix (#18) ✅ DONE (foundation)
- [x] ResolveStore registered as Livewire persistent middleware + self-detect mode (host/storefront precedence > stale admin session) — fixes current_store on /livewire/update for nested components; +regression tests, live-verified

### Phase 7: Admin Panel ✅ DONE (admin)
- [x] Layout (Flux Sidebar, TopBar w/ store selector, Breadcrumbs, toast host, dark mode) + Dashboard (KPIs/deltas, 30-day chart, recent orders, date range)
- [x] Products (Index bulk/filter/search + Form variant matrix + MediaManager), Orders (Index tabs + Show timeline/fulfillment/refund/confirm-payment), Collections, Customers, Discounts, Settings (General/Domains/Shipping/Taxes/Checkout/Notifications), Themes Index/Editor, Pages, Navigation (drag), Analytics, Search Settings, Apps, Developers (Sanctum tokens + webhooks) — 31 components
- [x] Policies enforced (Owner/Admin/Staff/Support matrix); dead starter views removed
- [x] Tests (Dashboard, ProductManagement, OrderManagement, DiscountManagement, Settings + SmokeTest = 55) green; browser-verified

### Phase 8: Search ✅ DONE (platform)
- [x] FTS5 virtual table products_fts + search_settings/search_queries + SearchService (search/autocomplete/sync/reindex) + ProductObserver (SearchServiceProvider)
- [x] Tests (Search, Autocomplete) green; "cotton" -> correct products verified

### Phase 9: Analytics ✅ DONE (platform)
- [x] analytics_events/analytics_daily migrations + AnalyticsService (track/getDailyMetrics/summarize) + AggregateAnalytics job + RecordOrderAnalytics listener
- [x] Tests (EventIngestion, Aggregation) green

### Phase 10: Apps & Webhooks + API + Sanctum ✅ DONE (platform)
- [x] apps/app_installations/oauth_*/webhook_subscriptions/webhook_deliveries + WebhookService (sign/verify HMAC) + DeliverWebhook job (backoff, circuit breaker)
- [x] Sanctum personal access tokens + ability middleware; REST API: storefront /api/storefront/v1/* (rate-limited) + admin /api/admin/v1/stores/{store}/* (Bearer + abilities) + Eloquent Resources
- [x] Tests (WebhookDelivery, WebhookSignature, SanctumToken, CartApi, Api/*) green; laravel/sanctum ^4.3 added

### Phase 11: Polish ✅ DONE (admin #9 + storefront #19)
- [x] Comprehensive demo seeders: 20 products w/ GD placeholder images (+3 renditions), 4 named customers w/ addresses, 8 orders across ALL statuses (paid/pending/fulfilled/cancelled/refunded/partially_refunded/voided + bank-transfer-pending), 2 refunds, 3 fulfillments
- [x] Storefront-branded auth (Login/Register on storefront layout); focus management (storefrontDialog helper: trap/escape/return) on cart drawer + search modal
- [x] Accessibility (skip links, ARIA, tablists, role=img charts), dark mode + responsive verified (375px, off-canvas sidebar, no overflow)
- [x] Styled 404/503 wired host-aware via bootstrap/app.php render hook (storefront branded; admin/api fall through); structured JSON logging channel (Phase 1)
- [x] npm run build (ships storefrontDialog JS); storage:link in place

### Phase 12: Full Verification 🔄 IN PROGRESS (team-lead)
- [x] Full Pest suite green: 437 passed / 1036 assertions / 0 failures
- [x] Pint clean (whole app)
- [x] migrate:fresh --seed clean (rich demo data verified)
- [ ] Playwright storefront smoke (browse, cart, checkout, account, search, 404)
- [ ] Playwright admin smoke (login, products, orders, fulfill/refund/confirm-payment)
- [ ] Review meeting / feature showcase

## Changelog
- (init) Repo baseline verified: PHP 8.4, Laravel 12, Livewire 4, Flux v2, Pest 4, SQLite. Progress tracker created.

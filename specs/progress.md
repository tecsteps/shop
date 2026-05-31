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

### Phase 6: Customer Accounts
- [ ] Account pages (Login, Register, Dashboard, Orders Index/Show, Addresses)
- [ ] Tests (CustomerAccount, AddressManagement)

### Phase 7: Admin Panel
- [ ] Layout (Sidebar, TopBar, Breadcrumbs) + Dashboard
- [ ] Products, Orders, Collections, Customers, Discounts, Settings, Themes, Pages, Navigation, Analytics, Search, Apps, Developers
- [ ] Tests (Dashboard, ProductManagement, OrderManagement, DiscountManagement, Settings)

### Phase 8: Search
- [ ] FTS5 migration + SearchService + ProductObserver + UI
- [ ] Tests (Search, Autocomplete)

### Phase 9: Analytics
- [ ] Migrations + AnalyticsService + AggregateAnalytics job
- [ ] Tests (EventIngestion, Aggregation)

### Phase 10: Apps & Webhooks
- [ ] Migrations + WebhookService + DeliverWebhook job + Sanctum tokens
- [ ] Tests (WebhookDelivery, WebhookSignature, SanctumToken)

### Phase 11: Polish
- [ ] Accessibility, responsive, dark mode, error pages, structured logging, demo seeders

### Phase 12: Full Verification
- [ ] Full Pest suite green
- [ ] Pint clean
- [ ] migrate:fresh --seed clean
- [ ] Playwright storefront smoke (browse, cart, checkout)
- [ ] Playwright admin smoke (login, manage products/orders)
- [ ] Review meeting / feature showcase

## Changelog
- (init) Repo baseline verified: PHP 8.4, Laravel 12, Livewire 4, Flux v2, Pest 4, SQLite. Progress tracker created.

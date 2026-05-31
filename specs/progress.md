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

### Phase 2: Catalog
- [ ] Migrations + models (Product, ProductOption, ProductOptionValue, ProductVariant, InventoryItem, Collection, ProductMedia)
- [ ] Enums (ProductStatus, VariantStatus, CollectionStatus, MediaType, MediaStatus, InventoryPolicy)
- [ ] Services (ProductService, VariantMatrixService, InventoryService, HandleGenerator)
- [ ] Media upload job
- [ ] Tests (ProductCrud, Variant, Inventory, Collection, MediaUpload, HandleGenerator)

### Phase 3: Themes, Pages, Navigation, Storefront Layout
- [ ] Migrations + models (Theme, ThemeFile, ThemeSettings, Page, NavigationMenu, NavigationItem)
- [ ] Enums (ThemeStatus, PageStatus, NavigationItemType)
- [ ] Storefront blade layout + components
- [ ] NavigationService + ThemeSettings service

### Phase 4: Cart, Checkout, Discounts, Shipping, Taxes
- [ ] Migrations + models (Cart, CartLine, Checkout, ShippingZone, ShippingRate, TaxSettings, Discount)
- [ ] Enums (CartStatus, CheckoutStatus, DiscountType, DiscountValueType, DiscountStatus, ShippingRateType, TaxMode)
- [ ] Services (CartService, DiscountService, ShippingCalculator, TaxCalculator, PricingEngine, CheckoutService)
- [ ] Value objects (PricingResult, TaxLine) + jobs (ExpireAbandonedCheckouts, CleanupAbandonedCarts)
- [ ] Storefront cart/checkout UI
- [ ] Tests (PricingEngine, DiscountCalculator, TaxCalculator, ShippingCalculator, CartVersion + feature tests)

### Phase 5: Payments, Orders, Fulfillment
- [ ] Migrations + models (Customer, CustomerAddress, Order, OrderLine, Payment, Refund, Fulfillment, FulfillmentLine)
- [ ] Enums (OrderStatus, FinancialStatus, FulfillmentStatus, PaymentMethod, PaymentStatus, RefundStatus, FulfillmentShipmentStatus)
- [ ] Mock PSP (PaymentProvider contract + MockPaymentProvider), OrderService, RefundService, FulfillmentService
- [ ] Events (OrderCreated/Paid/Fulfilled/Cancelled/Refunded) + bank transfer confirmation + jobs
- [ ] Tests (MockPaymentProvider, PaymentService, BankTransferConfirmation, OrderCreation, Refund, Fulfillment)

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

# Shop Implementation Progress

> Living document tracking the implementation of the shop system per specs/*.md.
> Updated after every iteration.

## Status Legend
- `[ ]` pending
- `[~]` in progress
- `[x]` done (implemented + tested)

---

## Iteration Log

| # | Date | Phase | Summary | Commit |
|---|------|-------|---------|--------|
| 0 | 2026-07-19 | Setup | Project scaffolding: sanctum + pest-plugin-browser deps, .env.testing, Herd site links (acme-fashion.test, acme-electronics.test), starter-kit cleanup | - |

---

## Phase Checklist

### Phase 1 — Foundation
- [ ] Config: database pragmas, session, cache, queue, auth (customer guard), logging (json + audit), cors
- [ ] All 46-table migrations (spec 01, dependency order)
- [ ] Core models: Organization, Store, StoreDomain, StoreUser, StoreSettings, User
- [ ] Enums (all, spec 05 §21)
- [ ] ResolveStore / CheckStoreRole / CustomerAuthenticate middleware
- [ ] BelongsToStore trait + StoreScope
- [ ] CustomerUserProvider
- [ ] Policies + ChecksStoreRole trait + Gates
- [ ] Rate limiters
- [ ] Tests: Tenancy (TenantResolutionTest, StoreIsolationTest)

### Phase 2 — Catalog
- [ ] Models: Product, ProductOption, ProductOptionValue, ProductVariant, InventoryItem, Collection, ProductMedia
- [ ] ProductService, VariantMatrixService, InventoryService, HandleGenerator
- [ ] ProcessMediaUpload job
- [ ] SanitizeHtml action
- [ ] Tests: ProductCrudTest, VariantTest, InventoryTest, CollectionTest, MediaUploadTest, HandleGeneratorTest

### Phase 3 — Themes / Pages / Navigation / Storefront layout
- [ ] Models: Theme, ThemeFile, ThemeSettings, Page, NavigationMenu, NavigationItem
- [ ] NavigationService, ThemeSettings service
- [ ] Storefront layout + components (product-card, price, badge, etc.)
- [ ] Storefront Livewire: Home, Collections Index/Show, Products Show, Pages Show

### Phase 4 — Cart / Checkout / Discounts / Shipping / Taxes
- [ ] Models: Cart, CartLine, Checkout, ShippingZone, ShippingRate, TaxSettings, Discount
- [ ] CartService, DiscountService, ShippingCalculator, TaxCalculator, PricingEngine, CheckoutService
- [ ] Value objects: PricingResult, TaxLine, Address, etc.
- [ ] Jobs: ExpireAbandonedCheckouts, CleanupAbandonedCarts
- [ ] Storefront cart/checkout Livewire UI + REST API endpoints
- [ ] Tests: PricingEngineTest, DiscountCalculatorTest, TaxCalculatorTest, ShippingCalculatorTest, CartVersionTest, CartServiceTest, CartApiTest, CheckoutFlowTest, CheckoutStateTest, PricingIntegrationTest, DiscountTest, ShippingTest, TaxTest

### Phase 5 — Payments / Orders / Fulfillment
- [ ] Models: Customer, CustomerAddress, Order, OrderLine, Payment, Refund, Fulfillment, FulfillmentLine
- [ ] MockPaymentProvider, PaymentService, OrderService, RefundService, FulfillmentService, CustomerService
- [ ] Events: OrderCreated, OrderPaid, OrderFulfilled, OrderCancelled, OrderRefunded, etc.
- [ ] Jobs: CancelUnpaidBankTransferOrders
- [ ] Tests: OrderCreationTest, RefundTest, FulfillmentTest, MockPaymentProviderTest, PaymentServiceTest, BankTransferConfirmationTest

### Phase 6 — Customer Accounts + Auth UI
- [ ] Admin auth: Login, Logout, ForgotPassword, ResetPassword (Livewire)
- [ ] Customer auth: Login, Register, ForgotPassword, ResetPassword (Livewire)
- [ ] Account pages: Dashboard, Orders Index/Show, Addresses Index
- [ ] Tests: AdminAuthTest, CustomerAuthTest, SanctumTokenTest, CustomerAccountTest, AddressManagementTest

### Phase 7 — Admin Panel
- [ ] Admin layout (sidebar, topbar, breadcrumbs, toasts)
- [ ] Dashboard (KPIs, chart, recent orders)
- [ ] Products (index, form with variants builder, media upload)
- [ ] Orders (index, show with fulfillment/refund modals, confirm payment)
- [ ] Collections, Customers, Discounts, Settings (general/domains/shipping/taxes), Themes, Pages, Navigation, Inventory
- [ ] Tests: DashboardTest, ProductManagementTest, OrderManagementTest, DiscountManagementTest, SettingsTest

### Phase 8 — Search
- [ ] FTS5 migration (products_fts), SearchService, ProductObserver
- [ ] SearchSettings model, admin Search Settings page
- [ ] Storefront Search Modal + Index
- [ ] Tests: SearchTest, AutocompleteTest

### Phase 9 — Analytics
- [ ] AnalyticsEvent, AnalyticsDaily models, AnalyticsService, AggregateAnalytics job
- [ ] Storefront event tracking + API endpoint
- [ ] Admin Analytics page
- [ ] Tests: EventIngestionTest, AggregationTest

### Phase 10 — Apps / Webhooks / Developers / Admin REST API
- [ ] Models: App, AppInstallation, OauthClient, OauthToken, WebhookSubscription, WebhookDelivery
- [ ] WebhookService, DeliverWebhook job, DispatchWebhooks listener
- [ ] Admin Apps + Developers pages (Sanctum token management)
- [ ] Admin REST API (/api/admin/v1): products, collections, orders, customers, discounts, platform
- [ ] Tests: WebhookDeliveryTest, WebhookSignatureTest, SanctumTokenTest, AdminProductApiTest, AdminOrderApiTest, StorefrontCartApiTest, StorefrontCheckoutApiTest

### Phase 11 — Seeders
- [ ] Exact demo data per spec 07 (2 stores, 5 users, 20+ products, collections, discounts, shipping, tax, pages, navigation, orders, customers)

### Phase 12 — Polish
- [ ] Error pages 404/503, dark mode, accessibility, structured logging
- [ ] Pint clean, full test suite green, fresh migrate+seed verified

### Browser E2E
- [ ] Pest browser tests per spec 08 (18 files, 143 tests)
- [ ] Playwright MCP verification of all acceptance criteria

### Final
- [ ] Review meeting: showcase all customer + admin features
